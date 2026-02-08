<?php

namespace App\Services;

use App\Models\CoverageDecisionMatrix;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Log;

class CoverageDecisionService
{
    /**
     * Check coverage and make decision based on decision matrix
     *
     * @param Policy $policy
     * @param int $serviceCategoryId
     * @param float $amount
     * @param string|null $description
     * @param array $additionalData
     * @return array Decision result with action, message, and notes
     */
    public function checkCoverage(
        Policy $policy,
        int $serviceCategoryId,
        float $amount,
        ?string $description = null,
        array $additionalData = []
    ): array {
        $insuranceCompanyId = $policy->insurance_company_id;
        
        // Get active decision matrix rules for this insurance company
        $rules = CoverageDecisionMatrix::forInsuranceCompany($insuranceCompanyId)
            ->active()
            ->orderedByPriority()
            ->get();

        // Check each rule in priority order
        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $policy, $serviceCategoryId, $amount, $description, $additionalData)) {
                return [
                    'action' => $rule->action,
                    'message' => $rule->rejection_message ?? $this->getDefaultMessage($rule->action),
                    'notes' => $this->formatReviewNotes($rule->review_notes_template ?? '', $policy, $serviceCategoryId, $amount),
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->rule_name,
                ];
            }
        }

        // Check if service category is covered by policy
        $benefit = PolicyBenefit::where('policy_id', $policy->id)
            ->where('service_category_id', $serviceCategoryId)
            ->where('is_enabled', true)
            ->first();

        if (!$benefit) {
            return [
                'action' => 'auto_reject',
                'message' => 'Service category is not covered by this policy.',
                'notes' => "Service category is not included in the policy benefits.",
                'rule_id' => null,
                'rule_name' => 'Default: Service Not Covered',
            ];
        }

        // Check if coverage limit is exceeded
        if ($benefit->benefit_amount > 0 && $benefit->remaining_amount < $amount) {
            return [
                'action' => 'flag_for_review',
                'message' => 'Coverage limit may be exceeded. Requires review.',
                'notes' => sprintf(
                    "Requested amount: %s, Remaining benefit: %s",
                    number_format($amount, 2),
                    number_format($benefit->remaining_amount, 2)
                ),
                'rule_id' => null,
                'rule_name' => 'Default: Coverage Limit Check',
            ];
        }

        // Default: Approved
        return [
            'action' => 'approved',
            'message' => 'Coverage approved.',
            'notes' => null,
            'rule_id' => null,
            'rule_name' => null,
        ];
    }

    /**
     * Check if a rule matches the given conditions
     */
    private function ruleMatches(
        CoverageDecisionMatrix $rule,
        Policy $policy,
        int $serviceCategoryId,
        float $amount,
        ?string $description,
        array $additionalData
    ): bool {
        $config = $rule->condition_config ?? [];

        switch ($rule->condition_type) {
            case 'service_category_not_covered':
                $categoryIds = $config['service_category_ids'] ?? [];
                return in_array($serviceCategoryId, $categoryIds);

            case 'service_category_coverage_limit_exceeded':
                $benefit = PolicyBenefit::where('policy_id', $policy->id)
                    ->where('service_category_id', $serviceCategoryId)
                    ->first();
                if (!$benefit || $benefit->benefit_amount <= 0) {
                    return false;
                }
                $threshold = $config['threshold_percentage'] ?? 90;
                $usagePercentage = ($benefit->used_amount / $benefit->benefit_amount) * 100;
                return $usagePercentage >= $threshold;

            case 'cost_threshold_exceeded':
                $threshold = $config['cost_threshold'] ?? 0;
                return $amount > $threshold;

            case 'keyword_match':
                $keywords = $config['keywords'] ?? [];
                if (empty($keywords) || !$description) {
                    return false;
                }
                $descriptionLower = strtolower($description);
                foreach ($keywords as $keyword) {
                    if (stripos($descriptionLower, strtolower($keyword)) !== false) {
                        return true;
                    }
                }
                return false;

            case 'procedure_type':
                $procedureTypes = $config['procedure_types'] ?? [];
                $procedureType = $additionalData['procedure_type'] ?? null;
                return $procedureType && in_array($procedureType, $procedureTypes);

            case 'visit_type_not_covered':
                $visitTypes = $config['visit_types'] ?? [];
                $visitType = $additionalData['visit_type'] ?? null;
                return $visitType && in_array($visitType, $visitTypes);

            default:
                return false;
        }
    }

    /**
     * Get default message based on action
     */
    private function getDefaultMessage(string $action): string
    {
        return match($action) {
            'auto_reject' => 'Service is not covered or does not meet policy requirements.',
            'flag_for_review' => 'Service requires manual review before approval.',
            'require_pre_authorization' => 'Pre-authorization is required for this service.',
            default => 'Coverage decision pending.',
        };
    }

    /**
     * Format review notes template with actual values
     */
    private function formatReviewNotes(string $template, Policy $policy, int $serviceCategoryId, float $amount): string
    {
        $serviceCategory = ServiceCategory::find($serviceCategoryId);
        
        $replacements = [
            '{policy_number}' => $policy->policy_number,
            '{service_category}' => $serviceCategory->name ?? 'Unknown',
            '{amount}' => number_format($amount, 2),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
