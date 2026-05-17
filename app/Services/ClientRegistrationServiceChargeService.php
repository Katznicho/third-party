<?php

namespace App\Services;

use App\Models\BusinessConnection;
use App\Models\InsuranceCompany;
use Illuminate\Support\Facades\Log;

/**
 * Applies Kashtre third-party vendor service charge tiers to insurer client registration premium.
 */
class ClientRegistrationServiceChargeService
{
    public function __construct(
        protected KashtreApiService $kashtreApi
    ) {}

    /**
     * First active connected Kashtre clinic for this insurer.
     */
    public function primaryConnectedBusinessId(InsuranceCompany $insuranceCompany): ?int
    {
        $id = BusinessConnection::query()
            ->where('insurance_company_id', $insuranceCompany->id)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('id')
            ->value('connected_business_id');

        return $id ? (int) $id : null;
    }

    /**
     * @param  float  $chargeableBase  Premium subtotal + training levy + stamp duty (before service charge).
     * @return array{
     *     amount: float,
     *     chargeable_base: float,
     *     connected_business_id: ?int,
     *     has_connection: bool,
     *     schedule_source: ?string,
     *     tier: ?array,
     *     formatted_service_charge: string
     * }
     */
    public function calculateForInsurer(InsuranceCompany $insuranceCompany, float $chargeableBase): array
    {
        $chargeableBase = max(0, round($chargeableBase, 2));
        $connectedBusinessId = $this->primaryConnectedBusinessId($insuranceCompany);

        if ($connectedBusinessId === null) {
            return $this->emptyResult($connectedBusinessId, $chargeableBase, false);
        }

        $vendorId = (int) $insuranceCompany->id;
        $result = $this->kashtreApi->calculateVendorServiceCharge(
            $connectedBusinessId,
            $chargeableBase,
            $vendorId
        );

        if (! is_array($result)) {
            Log::warning('ClientRegistrationServiceCharge: Kashtre calculate returned empty', [
                'insurance_company_id' => $insuranceCompany->id,
                'connected_business_id' => $connectedBusinessId,
                'chargeable_base' => $chargeableBase,
            ]);

            return $this->emptyResult($connectedBusinessId, $chargeableBase, true);
        }

        $amount = round((float) ($result['service_charge'] ?? 0), 2);

        return [
            'amount' => $amount,
            'chargeable_base' => $chargeableBase,
            'connected_business_id' => $connectedBusinessId,
            'has_connection' => true,
            'schedule_source' => $result['schedule_source'] ?? null,
            'tier' => is_array($result['tier'] ?? null) ? $result['tier'] : null,
            'formatted_service_charge' => $result['formatted_service_charge'] ?? ('UGX '.number_format($amount, 2)),
        ];
    }

    /**
     * @return array{
     *     amount: float,
     *     chargeable_base: float,
     *     connected_business_id: ?int,
     *     has_connection: bool,
     *     schedule_source: ?string,
     *     tier: ?array,
     *     formatted_service_charge: string
     * }
     */
    protected function emptyResult(?int $connectedBusinessId, float $chargeableBase, bool $hasConnection): array
    {
        return [
            'amount' => 0.0,
            'chargeable_base' => $chargeableBase,
            'connected_business_id' => $connectedBusinessId,
            'has_connection' => $hasConnection,
            'schedule_source' => null,
            'tier' => null,
            'formatted_service_charge' => $hasConnection ? 'UGX 0.00' : 'Not configured',
        ];
    }
}
