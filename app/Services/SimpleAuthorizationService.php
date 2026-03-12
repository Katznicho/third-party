<?php

namespace App\Services;

use App\Models\PreAuthorization;
use App\Models\InsuranceCompany;
use App\Models\AuthorizationAuditLog;
use App\Services\KashtreApiService;
use Illuminate\Support\Facades\Log;

class SimpleAuthorizationService
{
    /**
     * Process a pre-authorization based on simple threshold settings
     *
     * @param PreAuthorization $preAuthorization
     * @return array Result with decision and approved_amount
     */
    public function process(PreAuthorization $preAuthorization): array
    {
        $insuranceCompany = $preAuthorization->policy->insuranceCompany ?? null;
        
        if (!$insuranceCompany) {
            Log::warning('SimpleAuthorizationService: No insurance company found', [
                'pre_authorization_id' => $preAuthorization->id,
            ]);
            return $this->createResult('flagged_for_review', $preAuthorization->requested_amount, 'No insurance company found');
        }

        $requestedAmount = $preAuthorization->requested_amount;
        
        // Check if auto-authorization is enabled
        if (!$insuranceCompany->enable_auto_authorization) {
            Log::info('SimpleAuthorizationService: Auto-authorization disabled, flagging for review', [
                'pre_authorization_id' => $preAuthorization->id,
                'insurance_company_id' => $insuranceCompany->id,
            ]);
            return $this->createResult('flagged_for_review', null, 'Auto-authorization is disabled');
        }

        // Auto-reject if amount exceeds threshold
        if ($insuranceCompany->auto_reject_min_amount && $requestedAmount >= $insuranceCompany->auto_reject_min_amount) {
            Log::info('SimpleAuthorizationService: Amount exceeds auto-reject threshold', [
                'pre_authorization_id' => $preAuthorization->id,
                'requested_amount' => $requestedAmount,
                'auto_reject_threshold' => $insuranceCompany->auto_reject_min_amount,
            ]);
            return $this->createResult('auto_rejected', 0, "Amount ({$requestedAmount}) exceeds auto-reject threshold ({$insuranceCompany->auto_reject_min_amount})");
        }

        // Auto-approve if amount is below threshold
        if ($insuranceCompany->auto_approve_max_amount && $requestedAmount <= $insuranceCompany->auto_approve_max_amount) {
            Log::info('SimpleAuthorizationService: Amount within auto-approve threshold', [
                'pre_authorization_id' => $preAuthorization->id,
                'requested_amount' => $requestedAmount,
                'auto_approve_threshold' => $insuranceCompany->auto_approve_max_amount,
            ]);
            return $this->createResult('auto_approved', $requestedAmount, "Amount ({$requestedAmount}) is within auto-approve threshold ({$insuranceCompany->auto_approve_max_amount})");
        }

        // Flag for manual review if above threshold
        if ($insuranceCompany->require_manual_review_above_amount && 
            $insuranceCompany->manual_review_threshold_amount && 
            $requestedAmount > $insuranceCompany->manual_review_threshold_amount) {
            Log::info('SimpleAuthorizationService: Amount requires manual review', [
                'pre_authorization_id' => $preAuthorization->id,
                'requested_amount' => $requestedAmount,
                'manual_review_threshold' => $insuranceCompany->manual_review_threshold_amount,
            ]);
            return $this->createResult('flagged_for_review', null, "Amount ({$requestedAmount}) exceeds manual review threshold ({$insuranceCompany->manual_review_threshold_amount})");
        }

        // Default: flag for review if no thresholds match
        Log::info('SimpleAuthorizationService: No thresholds matched, flagging for review', [
            'pre_authorization_id' => $preAuthorization->id,
            'requested_amount' => $requestedAmount,
        ]);
        return $this->createResult('flagged_for_review', null, 'No matching authorization rules');
    }

    /**
     * Manually approve a pre-authorization
     */
    public function manuallyApprove(PreAuthorization $preAuthorization, ?float $approvedAmount = null, ?string $notes = null): bool
    {
        $approvedAmount = $approvedAmount ?? $preAuthorization->requested_amount;
        
        try {
            $preAuthorization->update([
                'status' => $approvedAmount < $preAuthorization->requested_amount ? 'partially_approved' : 'approved',
                'approved_amount' => $approvedAmount,
                'approval_date' => now(),
                'approved_by' => auth()->id(),
                'approval_notes' => $notes,
                'authorization_method' => 'manual',
            ]);

            if (!$preAuthorization->approval_id) {
                $preAuthorization->generateApprovalId();
            }

            $this->logDecision($preAuthorization, 'manually_approved', $approvedAmount, $notes);

            $this->syncInsuranceAuthorization($preAuthorization, 'completed', $approvedAmount);
            $this->notifyKashtreOfDecision($preAuthorization, 'approved', $approvedAmount);

            return true;
        } catch (\Exception $e) {
            Log::error('SimpleAuthorizationService: Failed to manually approve', [
                'pre_authorization_id' => $preAuthorization->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Manually reject a pre-authorization
     */
    public function manuallyReject(PreAuthorization $preAuthorization, string $rejectionReason): bool
    {
        try {
            $preAuthorization->update([
                'status' => 'rejected',
                'approved_amount' => 0,
                'rejection_reason' => $rejectionReason,
                'authorization_method' => 'manual',
            ]);

            $this->logDecision($preAuthorization, 'manually_rejected', 0, null, $rejectionReason);

            $this->syncInsuranceAuthorization($preAuthorization, 'rejected', 0);
            $this->notifyKashtreOfDecision($preAuthorization, 'rejected', 0, $rejectionReason);

            return true;
        } catch (\Exception $e) {
            Log::error('SimpleAuthorizationService: Failed to manually reject', [
                'pre_authorization_id' => $preAuthorization->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update the corresponding InsuranceAuthorization record after a manual decision.
     */
    protected function syncInsuranceAuthorization(PreAuthorization $preAuthorization, string $newStatus, float $approvedAmount): void
    {
        $invoiceNumber = null;
        if (preg_match('/Invoice\s+(\S+)/', $preAuthorization->request_description ?? '', $m)) {
            $invoiceNumber = $m[1];
        }

        $insuranceAuth = null;
        if ($invoiceNumber) {
            $insuranceAuth = \App\Models\InsuranceAuthorization::where('policy_id', $preAuthorization->policy_id)
                ->where('external_invoice_number', $invoiceNumber)
                ->first();
        }
        if (!$insuranceAuth) {
            $insuranceAuth = \App\Models\InsuranceAuthorization::where('policy_id', $preAuthorization->policy_id)
                ->where('status', 'pending_review')
                ->latest()
                ->first();
        }

        if (!$insuranceAuth) {
            Log::warning('SimpleAuthorizationService: No InsuranceAuthorization found to sync', [
                'pre_authorization_id' => $preAuthorization->id,
                'invoice_number' => $invoiceNumber,
            ]);
            return;
        }

        $updateData = [
            'status' => $newStatus,
            'completed_at' => now(),
        ];

        if ($newStatus === 'completed' && $approvedAmount < (float) $insuranceAuth->insurance_total) {
            $diff = round((float) $insuranceAuth->insurance_total - $approvedAmount, 2);
            $updateData['insurance_total'] = $approvedAmount;
            $updateData['client_total'] = round((float) $insuranceAuth->client_total + $diff, 2);
        }

        $insuranceAuth->update($updateData);

        if ($newStatus === 'completed') {
            $policyBenefit = null;
            $meta = $insuranceAuth->metadata ?? [];
            if (!empty($meta['policy_benefit_id'])) {
                $policyBenefit = \App\Models\PolicyBenefit::find($meta['policy_benefit_id']);
            }
            if ($policyBenefit) {
                $policyBenefit->used_amount = (float) $policyBenefit->used_amount + $approvedAmount;
                $policyBenefit->updateRemainingAmount();
                Log::info('SimpleAuthorizationService: Policy benefit updated after manual approval', [
                    'benefit_id' => $policyBenefit->id,
                    'used_amount' => $policyBenefit->used_amount,
                    'remaining_amount' => $policyBenefit->remaining_amount,
                ]);
            }
        }

        Log::info('SimpleAuthorizationService: InsuranceAuthorization synced', [
            'insurance_authorization_id' => $insuranceAuth->id,
            'new_status' => $newStatus,
            'approved_amount' => $approvedAmount,
        ]);
    }

    /**
     * Notify Kashtre that the authorization decision has been made so it can update
     * the invoice and optionally show the insurance modal to the user.
     */
    protected function notifyKashtreOfDecision(PreAuthorization $preAuthorization, string $decision, float $approvedAmount, ?string $rejectionReason = null): void
    {
        $invoiceNumber = null;
        if (preg_match('/Invoice\s+(\S+)/', $preAuthorization->request_description ?? '', $m)) {
            $invoiceNumber = $m[1];
        }

        $insuranceAuth = null;
        if ($invoiceNumber) {
            $insuranceAuth = \App\Models\InsuranceAuthorization::where('policy_id', $preAuthorization->policy_id)
                ->where('external_invoice_number', $invoiceNumber)
                ->first();
        }

        $payload = [
            'authorization_reference' => $insuranceAuth->authorization_reference ?? null,
            'kashtre_invoice_id' => $insuranceAuth->kashtre_invoice_id ?? null,
            'external_invoice_number' => $invoiceNumber,
            'decision' => $decision,
            'approved_amount' => $approvedAmount,
            'insurance_total' => $insuranceAuth ? (float) $insuranceAuth->insurance_total : $approvedAmount,
            'client_total' => $insuranceAuth ? (float) $insuranceAuth->client_total : 0,
            'breakdown' => $insuranceAuth->breakdown ?? null,
            'rejection_reason' => $rejectionReason,
            'decided_at' => now()->toIso8601String(),
        ];

        try {
            $kashtreApi = app(KashtreApiService::class);
            $result = $kashtreApi->notifyAuthorizationDecision($payload);

            Log::info('SimpleAuthorizationService: Kashtre notified of authorization decision', [
                'decision' => $decision,
                'kashtre_invoice_id' => $payload['kashtre_invoice_id'],
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::warning('SimpleAuthorizationService: Failed to notify Kashtre of authorization decision (non-blocking)', [
                'decision' => $decision,
                'kashtre_invoice_id' => $payload['kashtre_invoice_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log authorization decision
     */
    protected function logDecision(
        PreAuthorization $preAuthorization,
        string $decision,
        ?float $approvedAmount,
        ?string $notes = null,
        ?string $rejectionReason = null
    ): void {
        $insuranceCompany = $preAuthorization->policy->insuranceCompany ?? null;
        
        if (!$insuranceCompany) {
            return;
        }

        AuthorizationAuditLog::create([
            'pre_authorization_id' => $preAuthorization->id,
            'insurance_company_id' => $insuranceCompany->id,
            'decision' => $decision,
            'authorization_method' => str_starts_with($decision, 'auto_') ? 'automatic' : 'manual',
            'requested_amount' => $preAuthorization->requested_amount,
            'approved_amount' => $approvedAmount,
            'rejected_amount' => $approvedAmount !== null ? ($preAuthorization->requested_amount - $approvedAmount) : $preAuthorization->requested_amount,
            'processed_by' => auth()->id(),
            'notes' => $notes,
            'rejection_reason' => $rejectionReason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Create result array
     */
    protected function createResult(string $decision, ?float $approvedAmount, ?string $reason = null): array
    {
        return [
            'decision' => $decision,
            'approved_amount' => $approvedAmount,
            'reason' => $reason,
        ];
    }
}
