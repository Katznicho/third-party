<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Policy;
use App\Payments\YoAPI;
use App\Services\PaymentCompletionService;
use App\Services\ProviderPaymentCompletionService;
use App\Support\YoPaymentsErrorFormatter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckPaymentStatus extends Command
{
    protected $signature = 'payments:check-status'; 
    protected $description = 'Check and update YoAPI payment statuses for pending payments';

    public function handle()
    {
        Log::info('=== CRON JOB STARTED: CheckPaymentStatus (Third-Party) ===', [
            'timestamp' => now(),
            'command' => 'payments:check-status',
            'server' => gethostname(),
            'php_version' => PHP_VERSION
        ]);

        // Get all pending payments that have mobile money transaction references
        $pendingPayments = Payment::where('status', 'pending')
            ->where('payment_method', 'mobile_money')
            ->whereNotNull('transaction_id')
            ->whereNotNull('payment_metadata')
            ->with(['client', 'policy'])
            ->get();

        Log::info('Found pending mobile money payments', [
            'count' => $pendingPayments->count(),
            'payments' => $pendingPayments->map(function($p) {
                return [
                    'id' => $p->id,
                    'payment_reference' => $p->payment_reference,
                    'transaction_id' => $p->transaction_id,
                    'amount' => $p->amount,
                    'created_at' => $p->created_at->toDateTimeString(),
                    'age_minutes' => now()->diffInMinutes($p->created_at),
                ];
            })->toArray()
        ]);

        if ($pendingPayments->isEmpty()) {
            Log::info('No pending mobile money payments found - CRON JOB EXITING');
            $this->info('No pending payments to check.');
            return;
        }

        if ($credentialsError = YoPaymentsErrorFormatter::credentialsError()) {
            Log::error('CheckPaymentStatus: Yo Payments not configured', ['message' => $credentialsError]);
            $this->error($credentialsError);

            return self::FAILURE;
        }

        $yoPayments = new YoAPI(
            config('payments.yo_username'),
            config('payments.yo_password')
        );

        $processedCount = 0;
        $completedCount = 0;
        $failedCount = 0;

        foreach ($pendingPayments as $index => $payment) {
            try {
                $transactionReference = $payment->transaction_id;
                
                if (!$transactionReference) {
                    Log::warning("No transaction reference found for payment ID: {$payment->id}", [
                        'payment_id' => $payment->id,
                        'payment_reference' => $payment->payment_reference,
                    ]);
                    continue;
                }

                Log::info("=== PROCESSING PAYMENT " . ($index + 1) . " OF " . $pendingPayments->count() . " ===", [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'transaction_id' => $transactionReference,
                    'amount' => $payment->amount,
                    'client_id' => $payment->client_id,
                    'created_at' => $payment->created_at->toDateTimeString(),
                    'age_minutes' => now()->diffInMinutes($payment->created_at),
                ]);

                try {
                    $statusCheck = $yoPayments->ac_transaction_check_status($transactionReference);
                } catch (\Throwable $e) {
                    Log::error("YoAPI status check exception for payment {$payment->id}", [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionReference,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                Log::info("YoAPI status check response for payment {$payment->id}", [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'transaction_id' => $transactionReference,
                    'response' => $statusCheck
                ]);

                if (isset($statusCheck['TransactionStatus'])) {
                    if ($statusCheck['TransactionStatus'] === 'SUCCEEDED') {
                        if ($payment->payment_type === 'provider_payment') {
                            Log::info('Provider payment Yo SUCCEEDED — completing and posting to Kashtre', [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                            ]);

                            $result = app(ProviderPaymentCompletionService::class)
                                ->completeProviderMobileMoneyPayment($payment, $statusCheck);

                            if ($result['success'] ?? false) {
                                $completedCount++;
                                Log::info('Provider payment completed and posted to Kashtre', [
                                    'payment_id' => $payment->id,
                                ]);
                            } else {
                                Log::error('Provider payment Yo succeeded but completion failed', [
                                    'payment_id' => $payment->id,
                                    'message' => $result['message'] ?? null,
                                ]);
                            }

                            $processedCount++;
                            continue;
                        }

                        DB::beginTransaction();

                        try {
                            Log::info("🎉 PAYMENT SUCCEEDED - Updating payment status to completed", [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                                'transaction_id' => $transactionReference,
                                'amount' => $payment->amount,
                            ]);

                            $payment->update([
                                'status' => 'completed',
                                'paid_amount' => $payment->amount,
                                'balance_amount' => 0,
                                'cleared_date' => now(),
                                'processed_at' => now(),
                                'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                    'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                    'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                                    'yo_transaction_completion_date' => $statusCheck['TransactionCompletionDate'] ?? null,
                                    'yo_issued_receipt_number' => $statusCheck['IssuedReceiptNumber'] ?? null,
                                    'completed_at' => now()->toDateTimeString(),
                                ]),
                            ]);

                            PaymentCompletionService::ensureTransactionAndAccountForCompletedPayment($payment->fresh());

                            if ($payment->payment_type === 'premium_payment' && $payment->policy_id) {
                                $policy = Policy::find($payment->policy_id);
                                if ($policy) {
                                    $policy->update([
                                        'status' => 'active',
                                        'is_paid' => true,
                                        'payment_date' => now(),
                                    ]);
                                }
                            }

                            DB::commit();
                            $completedCount++;
                            Log::info("Payment status updated to completed", [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                            ]);
                            $processedCount++;
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error("Failed to update payment {$payment->id} status", [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                                'status_response' => $statusCheck,
                            ]);
                        }

                        continue;
                    }

                    DB::beginTransaction();

                    try {
                        if ($statusCheck['TransactionStatus'] === 'PENDING') {
                            Log::info("Payment still pending - no action taken", [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                                'status' => 'PENDING'
                            ]);
                            // Update metadata with latest status check
                            $payment->update([
                                'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                    'last_status_check' => now()->toDateTimeString(),
                                    'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                    'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                                ]),
                            ]);

                        } elseif ($statusCheck['TransactionStatus'] === 'FAILED') {
                            Log::warning("Payment FAILED - Updating payment status", [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                                'transaction_id' => $transactionReference,
                                'amount' => $payment->amount,
                            ]);
                            

                            // Update payment status to failed
                            $payment->update([
                                'status' => 'failed',
                                'failure_reason' => $statusCheck['StatusMessage'] ?? $statusCheck['ErrorMessage'] ?? 'Payment failed via Yo Payments',
                                'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                    'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                    'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                                    'yo_error_message' => $statusCheck['ErrorMessage'] ?? null,
                                    'failed_at' => now()->toDateTimeString(),
                                ]),
                            ]);

                            $failedCount++;
                            Log::info("Payment status updated to failed", [
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->payment_reference,
                            ]);
                        }

                        DB::commit();
                        $processedCount++;

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Failed to update payment {$payment->id} status", [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'status_response' => $statusCheck
                        ]);
                    }

                } else {
                    Log::warning("No valid status returned for payment ID: {$payment->id}", [
                        'payment_id' => $payment->id,
                        'payment_reference' => $payment->payment_reference,
                        'response' => $statusCheck,
                        'detail' => YoPaymentsErrorFormatter::formatStatusCheckFailure(is_array($statusCheck) ? $statusCheck : []),
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Error checking status for payment {$payment->id}", [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('=== CRON JOB COMPLETED: CheckPaymentStatus (Third-Party) ===', [
            'total_checked' => $pendingPayments->count(),
            'processed' => $processedCount,
            'completed' => $completedCount,
            'failed' => $failedCount,
            'still_pending' => $pendingPayments->count() - $completedCount - $failedCount,
            'timestamp' => now(),
            'command' => 'payments:check-status',
        ]);

        $this->info("Checked {$pendingPayments->count()} payments. Completed: {$completedCount}, Failed: {$failedCount}, Still Pending: " . ($pendingPayments->count() - $completedCount - $failedCount));
    }
}
