<?php

namespace App\Services;

use App\Models\ConnectedCompanyItemCoverage;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\ServiceCategory;

/**
 * Plan-level coverage % per service category (plan_service_category pivot).
 * Snapshotted on policy_benefits at enrollment; authorization uses that benefit row for the visit category.
 * When &lt; 100%, applies to all invoice lines for the visit (overrides provider per-item %).
 */
class PlanServiceCategoryCoverageService
{
    /**
     * Coverage % for this policy visit (service category on the invoice/client).
     * Priority: policy benefit snapshot → live plan pivot → 100%.
     */
    public static function coveragePercentForVisit(Policy $policy, ?ServiceCategory $serviceCategory): float
    {
        if (! $serviceCategory) {
            return 100.0;
        }

        $policy->loadMissing(['principalMember.plan.serviceCategories']);

        $planPivotPercent = self::planPivotCoveragePercent($policy, $serviceCategory);

        $benefit = PolicyBenefit::query()
            ->where('policy_id', $policy->id)
            ->where('service_category_id', $serviceCategory->id)
            ->where('is_enabled', true)
            ->first();

        if ($benefit && $benefit->coverage_percent !== null) {
            $benefitPercent = ConnectedCompanyItemCoverage::normalizePercent((float) $benefit->coverage_percent);
            // Legacy rows default to 100% before backfill: prefer plan when plan is stricter
            if ($benefitPercent >= 100.0 && $planPivotPercent < 100.0) {
                return $planPivotPercent;
            }

            return $benefitPercent;
        }

        return $planPivotPercent;
    }

    private static function planPivotCoveragePercent(Policy $policy, ServiceCategory $serviceCategory): float
    {
        $principal = $policy->principalMember;
        if (! $principal || ! $principal->plan_id) {
            return 100.0;
        }

        /** @var Plan|null $plan */
        $plan = $principal->plan;
        if (! $plan) {
            return 100.0;
        }

        $category = $plan->serviceCategories->firstWhere('id', $serviceCategory->id);
        if (! $category || ! ($category->pivot->is_enabled ?? true)) {
            return 100.0;
        }

        return ConnectedCompanyItemCoverage::normalizePercent(
            (float) ($category->pivot->coverage_percent ?? 100)
        );
    }

    /**
     * When plan category coverage is below 100%, it overrides per-item connected-provider coverage.
     */
    public static function overridesProviderItemCoverage(float $planCoveragePercent): bool
    {
        return $planCoveragePercent < 100.0;
    }
}
