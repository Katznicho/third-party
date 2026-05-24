<?php

namespace App\Services;

use App\Models\ConnectedCompanyItemCoverage;
use App\Models\InsuranceAuthorization;
use Illuminate\Support\Collection;

/**
 * KN flag: items already covered by a higher-priority insurer before this authorization (cascade).
 */
class AuthorizationPriorCoverageService
{
    /**
     * @return array{
     *     is_follow_up: bool,
     *     prior_insurers: array<int, array<string, mixed>>,
     *     lines: array<int, array<string, mixed>>
     * }
     */
    public function contextForAuthorization(InsuranceAuthorization $authorization): array
    {
        $metadata = is_array($authorization->metadata) ? $authorization->metadata : [];
        $cascadeMeta = is_array($metadata['authorization_cascade'] ?? null)
            ? $metadata['authorization_cascade']
            : [];

        $priorInsurers = collect($cascadeMeta['prior_authorizations'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        $linesFromPayload = $this->linesFromItemPayload($metadata['items'] ?? []);
        $linesFromSiblings = $this->linesFromPriorAuthorizations($authorization);

        $lines = $linesFromPayload->isNotEmpty()
            ? $linesFromPayload
            : $linesFromSiblings;

        $isFollowUp = ! empty($cascadeMeta['is_follow_up'])
            || $priorInsurers !== []
            || $lines->isNotEmpty()
            || $linesFromSiblings->isNotEmpty();

        if ($priorInsurers === [] && $linesFromSiblings->isNotEmpty()) {
            $priorInsurers = $this->priorInsurersFromSiblingAuthorizations($authorization);
        }

        return [
            'is_follow_up' => $isFollowUp,
            'prior_insurers' => $priorInsurers,
            'lines' => $lines->values()->all(),
        ];
    }

    /**
     * Match prior coverage to a line item row (by code, then name).
     *
     * @param  array<int, array<string, mixed>>  $priorLines
     * @return array<string, mixed>|null
     */
    public function matchPriorLine(?string $code, ?string $name, array $priorLines): ?array
    {
        $codeKey = $code !== null && trim($code) !== '' ? mb_strtolower(trim($code)) : '';
        $nameKey = $name !== null && trim($name) !== '' ? mb_strtolower(trim($name)) : '';

        foreach ($priorLines as $line) {
            $lineCode = mb_strtolower(trim((string) ($line['code'] ?? '')));
            $lineName = mb_strtolower(trim((string) ($line['name'] ?? '')));

            if ($codeKey !== '' && $lineCode !== '' && $codeKey === $lineCode) {
                return $line;
            }
            if ($nameKey !== '' && $lineName !== '' && $nameKey === $lineName) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function linesFromItemPayload(array $items): Collection
    {
        $lines = collect();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $prior = is_array($item['prior_coverage'] ?? null) ? $item['prior_coverage'] : null;
            $coveredAmount = (float) ($prior['prior_covered_amount'] ?? $item['prior_covered_amount'] ?? 0);
            if ($coveredAmount <= 0 && empty($item['already_covered_by_prior_insurer'])) {
                continue;
            }

            $lines->push([
                'code' => $item['code'] ?? null,
                'name' => $item['name'] ?? ($item['displayName'] ?? null),
                'prior_insurer_name' => $prior['prior_insurer_name'] ?? $item['prior_insurer_name'] ?? null,
                'prior_covered_amount' => $coveredAmount,
                'coverage_percent' => (float) ($prior['coverage_percent'] ?? $item['prior_coverage_percent'] ?? 0),
            ]);
        }

        return $lines->unique(fn (array $row) => mb_strtolower(trim((string) ($row['code'] ?? $row['name'] ?? ''))));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function linesFromPriorAuthorizations(InsuranceAuthorization $authorization): Collection
    {
        $lines = collect();

        foreach ($this->priorAuthorizationsQuery($authorization)->get() as $prior) {
            $insurerName = $prior->insuranceCompany?->name ?? 'Prior insurer';
            $breakdown = is_array($prior->breakdown) ? $prior->breakdown : [];
            $excludedItems = is_array($breakdown['excluded_items'] ?? null) ? $breakdown['excluded_items'] : [];

            foreach ($excludedItems as $ex) {
                if (! is_array($ex) || ($ex['reason_scope'] ?? '') !== ConnectedCompanyItemCoverage::REASON_SCOPE_PARTIAL) {
                    continue;
                }

                $coveredAmount = (float) ($ex['covered_amount'] ?? 0);
                if ($coveredAmount <= 0) {
                    continue;
                }

                $lines->push([
                    'code' => $ex['code'] ?? null,
                    'name' => $ex['name'] ?? null,
                    'prior_insurer_name' => $insurerName,
                    'prior_covered_amount' => $coveredAmount,
                    'coverage_percent' => (float) ($ex['coverage_percent'] ?? 0),
                    'prior_authorization_reference' => $prior->authorization_reference,
                ]);
            }
        }

        return $lines->unique(fn (array $row) => mb_strtolower(trim((string) ($row['code'] ?? $row['name'] ?? ''))));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function priorInsurersFromSiblingAuthorizations(InsuranceAuthorization $authorization): array
    {
        return $this->priorAuthorizationsQuery($authorization)
            ->with('insuranceCompany:id,name')
            ->get()
            ->map(fn (InsuranceAuthorization $prior) => [
                'vendor_name' => $prior->insuranceCompany?->name,
                'authorization_reference' => $prior->authorization_reference,
                'insurance_total' => (float) ($prior->insurance_total ?? 0),
                'requested_at' => $prior->requested_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function priorAuthorizationsQuery(InsuranceAuthorization $authorization)
    {
        $query = InsuranceAuthorization::query()
            ->where('kashtre_invoice_id', $authorization->kashtre_invoice_id)
            ->where('id', '!=', $authorization->id)
            ->orderBy('requested_at')
            ->orderBy('id');

        if ($authorization->requested_at) {
            $query->where(function ($q) use ($authorization) {
                $q->where('requested_at', '<', $authorization->requested_at)
                    ->orWhere(function ($q2) use ($authorization) {
                        $q2->where('requested_at', $authorization->requested_at)
                            ->where('id', '<', $authorization->id);
                    });
            });
        }

        return $query;
    }
}
