<?php

namespace App\Services;

use App\Models\PreAuthorization;
use App\Models\InsuranceCompany;
use App\Models\AuthorizationAuditLog;
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

            // Log the decision
            $this->logDecision($preAuthorization, 'manually_approved', $approvedAmount, $notes);

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

            // Log the decision
            $this->logDecision($preAuthorization, 'manually_rejected', 0, null, $rejectionReason);

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
