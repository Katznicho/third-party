<?php

namespace App\Services;

use App\Models\ConnectedCompanyItemCoverage;
use Illuminate\Support\Collection;

class ConnectedCompanyItemCoverageService
{
    public const REASON_SOURCE_PLAN = 'plan_service_category';

    public const REASON_SOURCE_PROVIDER_ITEM = 'provider_item';

    /**
     * Apply partial coverage (1–99%) for items not already fully excluded.
     *
     * When $planServiceCategoryCoveragePercent is set and &lt; 100, it applies to every line
     * for the visit and overrides per-item connected-company coverage.
     *
     * @param  array<int, array<string, mixed>>  $itemsPayload
     * @param  array<int, array<string, mixed>>  $excludedItemDetails
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    public function applyPartialCoverageToAuthorization(
        array $itemsPayload,
        array $excludedItemDetails,
        int $insuranceCompanyId,
        int $businessConnectionId,
        ?float $planServiceCategoryCoveragePercent = null,
        ?string $planServiceCategoryName = null
    ): array {
        if ($itemsPayload === []) {
            return [0.0, $excludedItemDetails];
        }

        $planPercent = $planServiceCategoryCoveragePercent !== null
            ? ConnectedCompanyItemCoverage::normalizePercent($planServiceCategoryCoveragePercent)
            : null;

        $usePlanOverride = $planPercent !== null
            && PlanServiceCategoryCoverageService::overridesProviderItemCoverage($planPercent);

        $coverageMap = $usePlanOverride
            ? collect()
            : ConnectedCompanyItemCoverage::activeMapForConnection(
                $insuranceCompanyId,
                $businessConnectionId
            );

        if (! $usePlanOverride && $coverageMap->isEmpty()) {
            return [0.0, $excludedItemDetails];
        }

        $excludedAmount = 0.0;
        $fullyExcludedCodes = $this->fullyExcludedCodeSet($excludedItemDetails);

        foreach ($itemsPayload as $item) {
            if (! is_array($item) || ! empty($item['kashtre_excluded'])) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? ''));
            $codeKey = $code !== '' ? mb_strtolower($code) : '';

            if ($codeKey !== '' && $fullyExcludedCodes->contains($codeKey)) {
                continue;
            }

            if ($usePlanOverride) {
                $percent = $planPercent;
                $reason = $planServiceCategoryName
                    ? "Plan service category ({$planServiceCategoryName}) at {$percent}%"
                    : "Plan service category at {$percent}%";
                $reasonSource = self::REASON_SOURCE_PLAN;
            } else {
                if ($codeKey === '') {
                    continue;
                }

                /** @var ConnectedCompanyItemCoverage|null $row */
                $row = $coverageMap->get($codeKey);
                $percent = $row
                    ? ConnectedCompanyItemCoverage::normalizePercent((float) $row->coverage_percent)
                    : 100.0;

                if ($percent >= 100.0) {
                    continue;
                }

                $reason = $row?->reason;
                $reasonSource = self::REASON_SOURCE_PROVIDER_ITEM;
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $lineTotal = round((float) ($item['total_amount'] ?? ($price * $quantity)), 2);

            if ($lineTotal <= 0) {
                continue;
            }

            $coveredAmount = round($lineTotal * ($percent / 100), 2);
            $uncovered = round($lineTotal - $coveredAmount, 2);

            if ($uncovered <= 0) {
                continue;
            }

            $excludedAmount += $uncovered;
            $excludedItemDetails[] = [
                'name' => $item['name'] ?? ($code !== '' ? $code : 'Line item'),
                'code' => $code !== '' ? $code : null,
                'amount' => $uncovered,
                'line_total' => $lineTotal,
                'coverage_percent' => $percent,
                'covered_amount' => $coveredAmount,
                'reason_scope' => ConnectedCompanyItemCoverage::REASON_SCOPE_PARTIAL,
                'reason' => $reason,
                'reason_source' => $reasonSource,
            ];

            if ($codeKey !== '') {
                $fullyExcludedCodes->push($codeKey);
            }
        }

        return [$excludedAmount, $excludedItemDetails];
    }

    /**
     * @param  array<int, array<string, mixed>>  $excludedItemDetails
     */
    private function fullyExcludedCodeSet(array $excludedItemDetails): Collection
    {
        return collect($excludedItemDetails)
            ->filter(function (array $ex) {
                if (($ex['reason_scope'] ?? '') === ConnectedCompanyItemCoverage::REASON_SCOPE_PARTIAL) {
                    return false;
                }

                return true;
            })
            ->pluck('code')
            ->filter()
            ->map(fn ($c) => mb_strtolower(trim((string) $c)))
            ->unique()
            ->values();
    }
}
