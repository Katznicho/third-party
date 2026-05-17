<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use Illuminate\Support\Facades\Log;

/**
 * Kashtre vendor service charge for insurer payments to a connected provider.
 */
class ProviderPaymentServiceChargeService
{
    public function __construct(
        protected KashtreApiService $kashtreApi
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     data?: array{
     *         amount: float,
     *         service_charge: float,
     *         total: float,
     *         formatted_amount: string,
     *         formatted_service_charge: string,
     *         formatted_total: string,
     *         schedule_source: ?string,
     *         tier: ?array
     *     },
     *     message?: string
     * }
     */
    public function preview(InsuranceCompany $insuranceCompany, int $kashtreBusinessId, float $amount): array
    {
        $amount = max(0, round($amount, 2));

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Enter a payment amount greater than zero.',
            ];
        }

        if ($kashtreBusinessId < 1) {
            return [
                'success' => false,
                'message' => 'This provider is not linked to a Kashtre business yet.',
            ];
        }

        $result = $this->kashtreApi->calculateVendorServiceCharge(
            $kashtreBusinessId,
            $amount,
            (int) $insuranceCompany->id
        );

        if (! is_array($result)) {
            Log::warning('ProviderPaymentServiceCharge: Kashtre calculate returned empty', [
                'insurance_company_id' => $insuranceCompany->id,
                'kashtre_business_id' => $kashtreBusinessId,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'message' => 'Could not calculate service charge. Check Kashtre connection and vendor charge tiers.',
            ];
        }

        $serviceCharge = round((float) ($result['service_charge'] ?? 0), 2);
        $total = round($amount + $serviceCharge, 2);

        return [
            'success' => true,
            'data' => [
                'amount' => $amount,
                'service_charge' => $serviceCharge,
                'total' => $total,
                'formatted_amount' => 'UGX '.number_format($amount, 2),
                'formatted_service_charge' => $result['formatted_service_charge']
                    ?? ('UGX '.number_format($serviceCharge, 2)),
                'formatted_total' => 'UGX '.number_format($total, 2),
                'schedule_source' => $result['schedule_source'] ?? null,
                'tier' => is_array($result['tier'] ?? null) ? $result['tier'] : null,
            ],
        ];
    }
}
