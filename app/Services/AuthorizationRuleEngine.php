<?php

namespace App\Services;

use App\Models\AuthorizationRule;
use App\Models\AuthorizationAuditLog;
use App\Models\PreAuthorization;
use App\Models\Policy;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthorizationRuleEngine
{
    /**
     * Process a pre-authorization request through the rules engine
     *
     * @param PreAuthorization $preAuthorization
     * @return array Result with decision, approved_amount, and matched rules
     */
    public function process(PreAuthorization $preAuthorization): array
    {
        Log::info('AuthorizationRuleEngine: Processing pre-authorization', [
            'pre_authorization_id' => $preAuthorization->id,
            'authorization_number' => $preAuthorization->authorization_number,
            'requested_amount' => $preAuthorization->requested_amount,
        ]);

        $insuranceCompanyId = $preAuthorization->policy->insurance_company_id ?? null;
        
        if (!$insuranceCompanyId) {
            Log::warning('AuthorizationRuleEngine: No insurance company found for pre-authorization', [
                'pre_authorization_id' => $preAuthorization->id,
            ]);
            return $this->createResult('flagged_for_review', $preAuthorization->requested_amount, null, 'No insurance company found');
        }

        // Get active rules for this insurance company, ordered by priority
        $rules = AuthorizationRule::forInsuranceCompany($insuranceCompanyId)
            ->active()
            ->byPriority()
            ->get();

        if ($rules->isEmpty()) {
            Log::info('AuthorizationRuleEngine: No active rules found, flagging for review', [
                'insurance_company_id' => $insuranceCompanyId,
            ]);
            return $this->createResult('flagged_for_review', $preAuthorization->requested_amount, null, 'No active rules configured');
        }

        // Build context for rule evaluation
        $context = $this->buildContext($preAuthorization);

        // Evaluate rules in priority order
        $matchedRules = [];
        $evaluationResults = [];

        foreach ($rules as $rule) {
            $evaluation = $this->evaluateRule($rule, $context);
            $evaluationResults[] = [
                'rule_id' => $rule->id,
                'rule_name' => $rule->rule_name,
                'matched' => $evaluation['matched'],
                'reason' => $evaluation['reason'],
            ];

            if ($evaluation['matched']) {
                $matchedRules[] = $rule;
                
                // If rule action is auto_approve or auto_reject, stop processing
                if (in_array($rule->action, ['auto_approve', 'auto_reject'])) {
                    break;
                }
            }
        }

        // Determine final decision based on matched rules
        $decision = $this->determineDecision($matchedRules, $preAuthorization->requested_amount);
        
        // Calculate approved amount
        $approvedAmount = $this->calculateApprovedAmount($decision, $matchedRules, $preAuthorization->requested_amount);

        // Log the decision
        $this->logDecision($preAuthorization, $decision, $approvedAmount, $matchedRules, $evaluationResults, $context);

        Log::info('AuthorizationRuleEngine: Decision made', [
            'pre_authorization_id' => $preAuthorization->id,
            'decision' => $decision,
            'approved_amount' => $approvedAmount,
            'matched_rules_count' => count($matchedRules),
        ]);

        return $this->createResult($decision, $approvedAmount, $matchedRules, null, $evaluationResults);
    }

    /**
     * Build context for rule evaluation
     */
    protected function buildContext(PreAuthorization $preAuthorization): array
    {
        $policy = $preAuthorization->policy;
        $client = $preAuthorization->client;
        $serviceCategory = $preAuthorization->serviceCategory;

        return [
            'amount' => $preAuthorization->requested_amount,
            'service_category_id' => $preAuthorization->service_category_id,
            'service_category_name' => $serviceCategory->name ?? null,
            'policy_id' => $preAuthorization->policy_id,
            'policy_type' => $policy->policy_type ?? null,
            'policy_status' => $policy->status ?? null,
            'client_id' => $preAuthorization->client_id,
            'client_tier' => $client->tier ?? null, // Assuming client has tier field
            'request_date' => $preAuthorization->request_date,
            'required_date' => $preAuthorization->required_date,
            'time_of_day' => now()->format('H:i'),
            'day_of_week' => now()->format('l'),
            'is_business_hours' => $this->isBusinessHours(),
        ];
    }

    /**
     * Evaluate a single rule against context
     */
    protected function evaluateRule(AuthorizationRule $rule, array $context): array
    {
        $conditions = $rule->conditions ?? [];

        switch ($rule->rule_type) {
            case 'amount':
                return $this->evaluateAmountRule($rule, $context, $conditions);
            
            case 'service_category':
                return $this->evaluateServiceCategoryRule($rule, $context, $conditions);
            
            case 'policy_type':
                return $this->evaluatePolicyTypeRule($rule, $context, $conditions);
            
            case 'client_tier':
                return $this->evaluateClientTierRule($rule, $context, $conditions);
            
            case 'time_based':
                return $this->evaluateTimeBasedRule($rule, $context, $conditions);
            
            case 'combined':
                return $this->evaluateCombinedRule($rule, $context, $conditions);
            
            default:
                return ['matched' => false, 'reason' => 'Unknown rule type'];
        }
    }

    /**
     * Evaluate amount-based rule
     */
    protected function evaluateAmountRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $amount = $context['amount'];

        $matched = true;
        $reasons = [];

        if (isset($conditions['min_amount']) && $amount < $conditions['min_amount']) {
            $matched = false;
            $reasons[] = "Amount ({$amount}) is below minimum ({$conditions['min_amount']})";
        }

        if (isset($conditions['max_amount']) && $amount > $conditions['max_amount']) {
            $matched = false;
            $reasons[] = "Amount ({$amount}) exceeds maximum ({$conditions['max_amount']})";
        }

        if (isset($conditions['exact_amount']) && $amount != $conditions['exact_amount']) {
            $matched = false;
            $reasons[] = "Amount ({$amount}) does not match exact amount ({$conditions['exact_amount']})";
        }

        return [
            'matched' => $matched,
            'reason' => $matched ? 'Amount conditions met' : implode(', ', $reasons),
        ];
    }

    /**
     * Evaluate service category-based rule
     */
    protected function evaluateServiceCategoryRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $serviceCategoryId = $context['service_category_id'];
        $allowedCategories = $conditions['service_category_ids'] ?? [];

        if (empty($allowedCategories)) {
            return ['matched' => false, 'reason' => 'No service categories specified in rule'];
        }

        $matched = in_array($serviceCategoryId, $allowedCategories);

        return [
            'matched' => $matched,
            'reason' => $matched 
                ? "Service category ({$serviceCategoryId}) is in allowed list" 
                : "Service category ({$serviceCategoryId}) is not in allowed list",
        ];
    }

    /**
     * Evaluate policy type-based rule
     */
    protected function evaluatePolicyTypeRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $policyType = $context['policy_type'];
        $allowedTypes = $conditions['policy_types'] ?? [];

        if (empty($allowedTypes)) {
            return ['matched' => false, 'reason' => 'No policy types specified in rule'];
        }

        $matched = in_array($policyType, $allowedTypes);

        return [
            'matched' => $matched,
            'reason' => $matched 
                ? "Policy type ({$policyType}) is in allowed list" 
                : "Policy type ({$policyType}) is not in allowed list",
        ];
    }

    /**
     * Evaluate client tier-based rule
     */
    protected function evaluateClientTierRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $clientTier = $context['client_tier'];
        $allowedTiers = $conditions['client_tiers'] ?? [];

        if (empty($allowedTiers)) {
            return ['matched' => false, 'reason' => 'No client tiers specified in rule'];
        }

        $matched = in_array($clientTier, $allowedTiers);

        return [
            'matched' => $matched,
            'reason' => $matched 
                ? "Client tier ({$clientTier}) is in allowed list" 
                : "Client tier ({$clientTier}) is not in allowed list",
        ];
    }

    /**
     * Evaluate time-based rule
     */
    protected function evaluateTimeBasedRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $matched = true;
        $reasons = [];

        if (isset($conditions['business_hours_only']) && $conditions['business_hours_only']) {
            if (!$context['is_business_hours']) {
                $matched = false;
                $reasons[] = 'Request is outside business hours';
            }
        }

        if (isset($conditions['allowed_hours'])) {
            $currentHour = (int) date('H');
            $allowedHours = $conditions['allowed_hours'];
            if (!in_array($currentHour, $allowedHours)) {
                $matched = false;
                $reasons[] = "Current hour ({$currentHour}) is not in allowed hours";
            }
        }

        return [
            'matched' => $matched,
            'reason' => $matched ? 'Time conditions met' : implode(', ', $reasons),
        ];
    }

    /**
     * Evaluate combined rule (multiple conditions)
     */
    protected function evaluateCombinedRule(AuthorizationRule $rule, array $context, array $conditions): array
    {
        $operator = $conditions['operator'] ?? 'AND'; // AND or OR
        
        $results = [];
        
        // Evaluate each sub-condition
        if (isset($conditions['amount'])) {
            $results[] = $this->evaluateAmountRule($rule, $context, $conditions['amount']);
        }
        if (isset($conditions['service_category'])) {
            $results[] = $this->evaluateServiceCategoryRule($rule, $context, $conditions['service_category']);
        }
        if (isset($conditions['policy_type'])) {
            $results[] = $this->evaluatePolicyTypeRule($rule, $context, $conditions['policy_type']);
        }
        // Add more condition types as needed

        if ($operator === 'AND') {
            $matched = collect($results)->every(fn($r) => $r['matched']);
        } else {
            $matched = collect($results)->contains(fn($r) => $r['matched']);
        }

        $reasons = collect($results)->pluck('reason')->toArray();

        return [
            'matched' => $matched,
            'reason' => $matched ? 'All conditions met' : implode('; ', $reasons),
        ];
    }

    /**
     * Determine final decision based on matched rules
     */
    protected function determineDecision(array $matchedRules, float $requestedAmount): string
    {
        if (empty($matchedRules)) {
            return 'flagged_for_review';
        }

        // Check for auto_reject first (highest priority)
        foreach ($matchedRules as $rule) {
            if ($rule->action === 'auto_reject') {
                return 'auto_rejected';
            }
        }

        // Check for auto_approve
        foreach ($matchedRules as $rule) {
            if ($rule->action === 'auto_approve') {
                return 'auto_approved';
            }
        }

        // Check for partially_approve
        foreach ($matchedRules as $rule) {
            if ($rule->action === 'partially_approve') {
                return 'partially_approved';
            }
        }

        // Default to flag for review
        return 'flagged_for_review';
    }

    /**
     * Calculate approved amount based on decision and rules
     */
    protected function calculateApprovedAmount(string $decision, array $matchedRules, float $requestedAmount): ?float
    {
        if ($decision === 'auto_approved') {
            return $requestedAmount;
        }

        if ($decision === 'auto_rejected') {
            return 0;
        }

        if ($decision === 'partially_approved') {
            // Find the partially_approve rule
            foreach ($matchedRules as $rule) {
                if ($rule->action === 'partially_approve') {
                    if ($rule->partial_approval_percentage) {
                        return $requestedAmount * ($rule->partial_approval_percentage / 100);
                    }
                    if ($rule->partial_approval_amount) {
                        return min($rule->partial_approval_amount, $requestedAmount);
                    }
                }
            }
        }

        return null; // Flagged for review - amount to be determined manually
    }

    /**
     * Check if current time is within business hours
     */
    protected function isBusinessHours(): bool
    {
        $hour = (int) date('H');
        return $hour >= 8 && $hour < 18; // 8 AM to 6 PM
    }

    /**
     * Log the authorization decision
     */
    protected function logDecision(
        PreAuthorization $preAuthorization,
        string $decision,
        ?float $approvedAmount,
        array $matchedRules,
        array $evaluationResults,
        array $context
    ): void {
        $insuranceCompanyId = $preAuthorization->policy->insurance_company_id;
        $rejectedAmount = $approvedAmount !== null ? ($preAuthorization->requested_amount - $approvedAmount) : null;

        AuthorizationAuditLog::create([
            'pre_authorization_id' => $preAuthorization->id,
            'authorization_rule_id' => !empty($matchedRules) ? $matchedRules[0]->id : null,
            'insurance_company_id' => $insuranceCompanyId,
            'decision' => $decision,
            'authorization_method' => 'automatic',
            'requested_amount' => $preAuthorization->requested_amount,
            'approved_amount' => $approvedAmount,
            'rejected_amount' => $rejectedAmount,
            'context_data' => $context,
            'rule_evaluation_results' => $evaluationResults,
            'processed_at' => now(),
        ]);
    }

    /**
     * Create result array
     */
    protected function createResult(
        string $decision,
        ?float $approvedAmount,
        ?array $matchedRules,
        ?string $reason = null,
        ?array $evaluationResults = null
    ): array {
        return [
            'decision' => $decision,
            'approved_amount' => $approvedAmount,
            'matched_rules' => $matchedRules,
            'reason' => $reason,
            'evaluation_results' => $evaluationResults,
        ];
    }
}
