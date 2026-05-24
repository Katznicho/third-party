<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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
                'history_ids' => array_values(array_map('intval', (array) ($meta['history_ids'] ?? []))),
            ]
        );

        if (! ($result['success'] ?? false)) {
            Log::error('ProviderPaymentCompletion: Kashtre record failed', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'kashtre_business_id' => $businessId,
                'insurance_company_id' => $insuranceCompanyId,
                'provider_amount' => $providerAmount,
                'error' => $result['error'] ?? null,
                'http_status' => $result['http_status'] ?? null,
            ]);

            $message = $result['error'] ?? 'Could not record payment on the provider ledger.';
            if (! empty($result['http_status'])) {
                $message .= ' (Kashtre HTTP '.$result['http_status'].')';
            }

            return [
                'success' => false,
                'message' => $message,
                'http_status' => $result['http_status'] ?? null,
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
     * Mark provider mobile money payment completed and post to Kashtre (atomic).
     *
     * @param  array<string, mixed>  $yoStatusCheck
     * @return array{success: bool, message?: string, data?: array, payment?: Payment}
     */
    public function completeProviderMobileMoneyPayment(Payment $payment, array $yoStatusCheck = []): array
    {
        if ($payment->payment_type !== 'provider_payment') {
            return ['success' => false, 'message' => 'Not a provider payment.'];
        }

        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];

        if (! empty($meta['kashtre_recorded'])) {
            return [
                'success' => true,
                'payment' => $payment,
                'data' => is_array($meta['kashtre_result'] ?? null) ? $meta['kashtre_result'] : [],
            ];
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'status' => 'completed',
                'paid_amount' => $payment->amount,
                'balance_amount' => 0,
                'cleared_date' => now(),
                'processed_at' => now(),
                'payment_metadata' => array_merge($meta, [
                    'yo_status' => $yoStatusCheck['TransactionStatus'] ?? ($meta['yo_status'] ?? null),
                    'yo_status_message' => $yoStatusCheck['StatusMessage'] ?? null,
                    'confirmed_at' => now()->toDateTimeString(),
                ]),
            ]);

            $ledger = $this->recordOnKashtreLedger($payment->fresh());
            if (! ($ledger['success'] ?? false)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => $ledger['message'] ?? 'Payment was confirmed on mobile money but could not be posted to the provider ledger.',
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'data' => $ledger['data'] ?? [],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ProviderPaymentCompletion: completeProviderMobileMoneyPayment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not complete payment: '.$e->getMessage(),
            ];
        }
    }

    /**
     * If Yo already succeeded but Kashtre ledger was missed, retry ledger only.
     *
     * @return array{success: bool, message?: string, data?: array}
     */
    public function syncKashtreLedgerIfMissing(Payment $payment): array
    {
        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];
        if (! empty($meta['kashtre_recorded'])) {
            return [
                'success' => true,
                'data' => is_array($meta['kashtre_result'] ?? null) ? $meta['kashtre_result'] : [],
            ];
        }

        if ($payment->status !== 'completed') {
            return ['success' => false, 'message' => 'Payment is not marked completed yet.'];
        }

        return $this->recordOnKashtreLedger($payment);
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
