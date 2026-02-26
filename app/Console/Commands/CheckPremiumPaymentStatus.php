<?php

namespace App\Console\Commands;

use App\Models\PolicyPremiumPayment;
use App\Models\Policy;
use App\Models\Payment;
use App\Payments\YoAPI;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckPremiumPaymentStatus extends Command
{
    protected $signature = 'payments:check-premium-status';
    protected $description = 'Check Yo status for pending policy premium payments and activate policy when confirmed';

    public function handle()
    {
        Log::info('=== CRON: CheckPremiumPaymentStatus ===', ['timestamp' => now()]);

        $pending = PolicyPremiumPayment::where('status', 'pending')
            ->whereNotNull('yo_transaction_reference')
            ->with(['policy', 'client'])
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending premium payments.');
            return 0;
        }

        $yoApi = new YoAPI(
            config('payments.yo_username'),
            config('payments.yo_password')
        );

        foreach ($pending as $premiumPayment) {
            try {
                $ref = $premiumPayment->yo_transaction_reference;
                $result = $yoApi->ac_transaction_check_status($ref);

                Log::info('YoAPI premium status check', [
                    'policy_premium_payment_id' => $premiumPayment->id,
                    'transaction_reference' => $ref,
                    'yo_result' => $result,
                ]);

                $status = strtoupper($result['Status'] ?? $result['TransactionStatus'] ?? '');
                $statusCode = $result['StatusCode'] ?? '';

                if ($status === 'SUCCEEDED' || $statusCode === 'SUCCEEDED') {
                    DB::beginTransaction();
                    try {
                        $premiumPayment->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                            'metadata' => array_merge($premiumPayment->metadata ?? [], [
                                'yo_status' => $status,
                                'confirmed_at' => now()->toDateTimeString(),
                            ]),
                        ]);

                        $policy = $premiumPayment->policy;
                        $policy->update([
                            'status' => 'active',
                            'is_paid' => true,
                            'payment_date' => now(),
                        ]);

                        Payment::create([
                            'payment_reference' => $premiumPayment->payment_reference,
                            'invoice_id' => null,
                            'policy_id' => $policy->id,
                            'client_id' => $premiumPayment->client_id,
                            'payment_type' => 'premium_payment',
                            'amount' => $premiumPayment->amount,
                            'paid_amount' => $premiumPayment->amount,
                            'balance_amount' => 0,
                            'payment_method' => 'mobile_money',
                            'mobile_money_number' => $premiumPayment->mobile_phone,
                            'transaction_id' => $ref,
                            'status' => 'completed',
                            'payment_date' => now(),
                            'processed_at' => now(),
                            'payment_notes' => 'Premium payment (Yo - confirmed by cron)',
                        ]);

                        DB::commit();
                        Log::info('Premium payment confirmed and policy activated', [
                            'policy_id' => $policy->id,
                            'policy_number' => $policy->policy_number,
                        ]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error activating policy after premium confirmation', [
                            'premium_payment_id' => $premiumPayment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } elseif ($status === 'FAILED' || $statusCode === 'FAILED') {
                    $premiumPayment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($premiumPayment->metadata ?? [], [
                            'yo_status' => $status,
                            'yo_message' => $result['StatusMessage'] ?? $result['ErrorMessage'] ?? null,
                        ]),
                    ]);
                    Log::info('Premium payment marked as failed', ['id' => $premiumPayment->id]);
                }
            } catch (\Exception $e) {
                Log::error('CheckPremiumPaymentStatus error', [
                    'premium_payment_id' => $premiumPayment->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
