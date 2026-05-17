<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * After Yo confirms collection, record the provider payment on Kashtre (ledger credit + service charge debit).
 */
class ProviderPaymentCompletionService
{
    public function __construct(
        protected KashtreApiService $kashtreApi
    ) {}

    /**
     * @return array{success: bool, message?: string, data?: array, already_recorded?: bool}
     */
    public function recordOnKashtreLedger(Payment $payment): array
    {
        if ($payment->payment_type !== 'provider_payment') {
            return ['success' => false, 'message' => 'Not a provider payment.'];
        }

        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];

        if (! empty($meta['kashtre_recorded'])) {
            return [
                'success' => true,
                'already_recorded' => true,
                'data' => is_array($meta['kashtre_result'] ?? null) ? $meta['kashtre_result'] : [],
            ];
        }

        $businessId = (int) ($meta['kashtre_business_id'] ?? 0);
        $insuranceCompanyId = (int) ($meta['insurance_company_id'] ?? 0);
        $providerAmount = (float) ($meta['provider_amount'] ?? 0);

        if ($businessId < 1 || $insuranceCompanyId < 1 || $providerAmount <= 0) {
            return ['success' => false, 'message' => 'Provider payment metadata is incomplete.'];
        }

        $result = $this->kashtreApi->recordInsurerPortalPayment(
            $businessId,
            $insuranceCompanyId,
            [
                'amount' => $providerAmount,
                'payment_method' => (string) $payment->payment_method,
                'reference' => $payment->payment_reference,
                'notes' => $payment->payment_notes,
            ]
        );

        if (! ($result['success'] ?? false)) {
            Log::error('ProviderPaymentCompletion: Kashtre record failed', [
                'payment_id' => $payment->id,
                'error' => $result['error'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Could not record payment on the provider ledger.',
            ];
        }

        $payment->update([
            'payment_metadata' => array_merge($meta, [
                'kashtre_recorded' => true,
                'kashtre_result' => $result['data'] ?? [],
                'kashtre_recorded_at' => now()->toDateTimeString(),
            ]),
        ]);

        return [
            'success' => true,
            'data' => $result['data'] ?? [],
        ];
    }

    /**
     * Build receipt session payload after Kashtre ledger is updated.
     *
     * @return array<string, mixed>
     */
    public function buildReceipt(Payment $payment, array $kashtreData = []): array
    {
        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];
        $paymentMethodOptions = \App\Models\InsuranceCompany::getPaymentMethodOptions();
        $method = (string) $payment->payment_method;

        return [
            'reference' => $kashtreData['payment']['reference'] ?? $payment->payment_reference,
            'amount' => (float) ($kashtreData['payment']['amount'] ?? ($meta['provider_amount'] ?? 0)),
            'service_charge' => (float) ($kashtreData['service_charge'] ?? ($meta['service_charge'] ?? 0)),
            'total_paid' => (float) ($kashtreData['total_paid'] ?? ($meta['total_collected'] ?? $payment->amount)),
            'payment_method' => $method,
            'payment_method_label' => $paymentMethodOptions[$method] ?? $method,
            'provider_name' => $meta['provider_name'] ?? 'Service provider',
            'insurer_name' => $meta['insurer_name'] ?? '',
            'paid_at' => now()->toIso8601String(),
            'new_balance' => (float) ($kashtreData['financial']['current_balance'] ?? 0),
            'connection_id' => (int) ($meta['connection_id'] ?? 0),
        ];
    }
}
