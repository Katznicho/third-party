<?php

namespace App\Services;

use App\Models\PreAuthorizationTrigger;
use App\Models\PreAuthorization;
use App\Models\Policy;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PreAuthorizationTriggerService
{
    /**
     * Check if pre-authorization is required based on triggers
     *
     * @param Policy $policy
     * @param int $serviceCategoryId
     * @param float $amount
     * @param string|null $description
     * @param array $additionalData
     * @return array|null Trigger result or null if no trigger matched
     */
    public function checkTriggers(
        Policy $policy,
        int $serviceCategoryId,
        float $amount,
        ?string $description = null,
        array $additionalData = []
    ): ?array {
        $insuranceCompanyId = $policy->insurance_company_id;

        // Get active triggers for this insurance company
        $triggers = PreAuthorizationTrigger::forInsuranceCompany($insuranceCompanyId)
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($triggers as $trigger) {
            if ($trigger->matches($serviceCategoryId, $amount, $description, $additionalData['keywords'] ?? [])) {
                return [
                    'trigger_id' => $trigger->id,
                    'trigger_type' => $trigger->trigger_type,
                    'trigger_name' => $trigger->trigger_name,
                    'requires_preauth' => true,
                    'auto_create' => $trigger->auto_create_preauth,
                    'require_manual_approval' => $trigger->require_manual_approval,
                    'auto_approval_limit' => $trigger->auto_approval_limit,
                ];
            }
        }

        return null;
    }

    /**
     * Auto-create pre-authorization if trigger requires it
     *
     * @param Policy $policy
     * @param int $serviceCategoryId
     * @param float $amount
     * @param string|null $description
     * @param array $additionalData
     * @return PreAuthorization|null
     */
    public function autoCreatePreAuthorization(
        Policy $policy,
        int $serviceCategoryId,
        float $amount,
        ?string $description = null,
        array $additionalData = []
    ): ?PreAuthorization {
        $triggerResult = $this->checkTriggers($policy, $serviceCategoryId, $amount, $description, $additionalData);

        if (!$triggerResult || !$triggerResult['auto_create']) {
            return null;
        }

        $trigger = PreAuthorizationTrigger::find($triggerResult['trigger_id']);
        $serviceCategory = ServiceCategory::find($serviceCategoryId);

        // Generate authorization number
        $authorizationNumber = $this->generateAuthorizationNumber($policy);

        // Determine status based on auto-approval limit
        $status = 'pending';
        $approvedAmount = null;
        $approvalDate = null;
        $approvedBy = null;

        if ($trigger->auto_approval_limit && $amount <= $trigger->auto_approval_limit) {
            $status = 'approved';
            $approvedAmount = $amount;
            $approvalDate = now()->toDateString();
            // Could set approved_by to system user if exists
        }

        $preAuth = PreAuthorization::create([
            'authorization_number' => $authorizationNumber,
            'approval_id' => $status === 'approved' ? $this->generateApprovalId($policy) : null,
            'policy_id' => $policy->id,
            'client_id' => $policy->principal_member_id,
            'service_category_id' => $serviceCategoryId,
            'request_description' => $description ?? "Auto-created for {$serviceCategory->name ?? 'service'}",
            'medical_justification' => "Auto-created by trigger: {$trigger->trigger_name}",
            'requested_by' => $additionalData['requested_by'] ?? 'System',
            'provider_name' => $additionalData['provider_name'] ?? null,
            'provider_address' => $additionalData['provider_address'] ?? null,
            'provider_phone' => $additionalData['provider_phone'] ?? null,
            'requested_amount' => $amount,
            'approved_amount' => $approvedAmount,
            'estimated_amount' => $amount,
            'status' => $status,
            'request_date' => now()->toDateString(),
            'required_date' => $additionalData['required_date'] ?? now()->addDays(7)->toDateString(),
            'approval_date' => $approvalDate,
            'approved_by' => $approvedBy,
            'expiry_date' => $additionalData['expiry_date'] ?? now()->addDays(30)->toDateString(),
            'triggered_by_trigger_id' => $trigger->id,
            'trigger_reason' => $this->mapTriggerTypeToReason($trigger->trigger_type),
        ]);

        Log::info('Pre-authorization auto-created', [
            'pre_authorization_id' => $preAuth->id,
            'authorization_number' => $authorizationNumber,
            'trigger_id' => $trigger->id,
            'trigger_name' => $trigger->trigger_name,
            'status' => $status,
        ]);

        return $preAuth;
    }

    /**
     * Generate unique authorization number
     */
    private function generateAuthorizationNumber(Policy $policy): string
    {
        $prefix = 'AUTH';
        $year = now()->format('Y');
        $month = now()->format('m');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    /**
     * Generate unique approval ID
     */
    private function generateApprovalId(Policy $policy): string
    {
        $prefix = 'APP';
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = strtoupper(Str::random(8));
        
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    /**
     * Map trigger type to trigger reason
     */
    private function mapTriggerTypeToReason(string $triggerType): string
    {
        return match($triggerType) {
            'high_cost_service', 'cost_threshold' => 'high_cost',
            'special_procedure' => 'special_procedure',
            'keyword_match' => 'keyword_match',
            default => 'manual',
        };
    }
}
