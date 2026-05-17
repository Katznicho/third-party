<?php

namespace App\Services;

use App\Models\ConnectedCompanyItemCoverage;
use Illuminate\Support\Collection;

class ConnectedCompanyItemCoverageService
{
    /**
     * Apply insurer-configured partial coverage (1–99%) for items not already fully excluded.
     *
     * @param  array<int, array<string, mixed>>  $itemsPayload
     * @param  array<int, array<string, mixed>>  $excludedItemDetails
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    public function applyPartialCoverageToAuthorization(
        array $itemsPayload,
        array $excludedItemDetails,
        int $insuranceCompanyId,
        int $businessConnectionId
    ): array {
        $excludedAmount = 0.0;
        $coverageMap = ConnectedCompanyItemCoverage::activeMapForConnection(
            $insuranceCompanyId,
            $businessConnectionId
        );

        if ($coverageMap->isEmpty() || $itemsPayload === []) {
            return [0.0, $excludedItemDetails];
        }

        $fullyExcludedCodes = $this->fullyExcludedCodeSet($excludedItemDetails);

        foreach ($itemsPayload as $item) {
            if (! is_array($item) || ! empty($item['kashtre_excluded'])) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $codeKey = mb_strtolower($code);
            if ($fullyExcludedCodes->contains($codeKey)) {
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
                'name' => $item['name'] ?? $code,
                'code' => $code,
                'amount' => $uncovered,
                'line_total' => $lineTotal,
                'coverage_percent' => $percent,
                'covered_amount' => $coveredAmount,
                'reason_scope' => ConnectedCompanyItemCoverage::REASON_SCOPE_PARTIAL,
                'reason' => $row?->reason,
            ];

            $fullyExcludedCodes->push($codeKey);
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
