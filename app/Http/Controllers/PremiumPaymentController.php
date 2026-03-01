<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyPremiumPayment;
use App\Models\Payment;
use App\Payments\YoAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PremiumPaymentController extends Controller
{
    /**
     * Show the pay premium page for a client (after client creation).
     */
    public function showPayPremium(Client $client)
    {
        $user = auth()->user();
        if (!$user->insurance_company_id) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        // Get the client's policy awaiting premium payment (inactive, not paid) for this insurance company
        $policy = $client->policies()
            ->where('insurance_company_id', $user->insurance_company_id)
            ->where('status', 'inactive')
            ->where('is_paid', false)
            ->latest()
            ->first();

        if (!$policy) {
            $activePolicy = $client->policies()
                ->where('insurance_company_id', $user->insurance_company_id)
                ->where('status', 'active')
                ->first();
            if ($activePolicy) {
                return redirect()->route('clients.show', $client)
                    ->with('info', 'This policy is already active. Premium has been paid.');
            }
            return redirect()->route('clients.show', $client)
                ->with('error', 'No pending premium payment found for this client.');
        }

        $amount = $policy->total_premium_due;
        $paymentReference = 'PREM-' . $policy->id . '-' . time();

        return view('clients.pay-premium', compact('client', 'policy', 'amount', 'paymentReference'));
    }

    /**
     * Process premium payment (Yo mobile money or cash).
     */
    public function processPayPremium(Client $client, Request $request)
    {
        $user = auth()->user();
        if (!$user->insurance_company_id) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        $policy = $client->policies()
            ->where('insurance_company_id', $user->insurance_company_id)
            ->where('status', 'inactive')
            ->where('is_paid', false)
            ->latest()
            ->first();

        if (!$policy) {
            return redirect()->route('clients.show', $client)
                ->with('error', 'No pending premium payment found for this client.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
            'payment_phone' => 'required_if:payment_method,mobile_money|nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = $policy->total_premium_due;
        $paymentReference = 'PREM-' . $policy->id . '-' . time();

        try {
            DB::beginTransaction();

            if ($validated['payment_method'] === 'mobile_money') {
                $phone = $validated['payment_phone'] ?? '';
                $phone = preg_replace('/\s+/', '', $phone);
                if (str_starts_with($phone, '+')) {
                    $phone = substr($phone, 1);
                } elseif (str_starts_with($phone, '0')) {
                    $phone = '256' . substr($phone, 1);
                }
                if (strlen($phone) < 9) {
                    return redirect()->back()->with('error', 'Please enter a valid mobile money number.')->withInput();
                }

                // In local environment, auto-complete without calling Yo
                if (app()->environment('local')) {
                    $policy->update([
                        'status' => 'active',
                        'is_paid' => true,
                        'payment_date' => now(),
                    ]);

                    Payment::create([
                        'payment_reference' => $paymentReference,
                        'invoice_id' => null,
                        'policy_id' => $policy->id,
                        'client_id' => $client->id,
                        'payment_type' => 'premium_payment',
                        'amount' => $amount,
                        'paid_amount' => $amount,
                        'balance_amount' => 0,
                        'payment_method' => 'mobile_money',
                        'mobile_money_number' => $phone,
                        'transaction_id' => 'LOCAL-TEST-' . uniqid(),
                        'status' => 'completed',
                        'payment_date' => now(),
                        'processed_at' => now(),
                        'payment_notes' => ($validated['notes'] ?? 'Premium payment (mobile money)') . ' [LOCAL AUTO-COMPLETE]',
                        'processed_by' => $user->id,
                    ]);

                    DB::commit();

                    return redirect()->route('clients.show', $client)
                        ->with('success', 'Premium paid automatically in local environment. Policy is now active.');
                }

                $yoApi = new YoAPI(
                    config('payments.yo_username'),
                    config('payments.yo_password')
                );
                $yoApi->set_instant_notification_url(config('payments.webhook_url'));
                $yoApi->set_external_reference($paymentReference);

                $narrative = 'Premium payment - Policy ' . $policy->policy_number . ' - ' . $client->full_name;
                if (strlen($narrative) > 160) {
                    $narrative = substr($narrative, 0, 157) . '...';
                }

                Log::info('Initiating Yo premium payment', [
                    'policy_id' => $policy->id,
                    'client_id' => $client->id,
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $paymentReference,
                ]);

                $yoResult = $yoApi->ac_deposit_funds($phone, (float) $amount, $narrative);

                Log::info('YoAPI premium payment response', ['result' => $yoResult]);

                if (isset($yoResult['Status']) && $yoResult['Status'] === 'OK' && !empty($yoResult['TransactionReference'])) {
                    $transactionRef = $yoResult['TransactionReference'];

                    // Create Payment record as pending so the cron can update it later
                    Payment::create([
                        'payment_reference' => $paymentReference,
                        'invoice_id' => null,
                        'policy_id' => $policy->id,
                        'client_id' => $client->id,
                        'payment_type' => 'premium_payment',
                        'amount' => $amount,
                        'paid_amount' => $amount,
                        'balance_amount' => 0,
                        'payment_method' => 'mobile_money',
                        'mobile_money_number' => $phone,
                        'transaction_id' => $transactionRef,
                        'status' => 'pending',
                        'payment_date' => now(),
                        'processed_at' => null,
                        'payment_notes' => $validated['notes'] ?? 'Premium payment (mobile money)',
                        'payment_metadata' => [
                            'yo_transaction_reference' => $transactionRef,
                            'yo_status' => $yoResult['Status'] ?? null,
                            'policy_id' => $policy->id,
                            'insurance_company_id' => $user->insurance_company_id,
                        ],
                        'processed_by' => $user->id,
                    ]);

                    DB::commit();
                    return redirect()->route('clients.pay-premium', $client)
                        ->with('success', 'Mobile money request sent. Please complete the payment on your phone. The policy will become active once payment is confirmed (usually within a few minutes).');
                }

                $errorMessage = $yoResult['StatusMessage'] ?? $yoResult['ErrorMessage'] ?? 'Unknown error';
                DB::rollBack();
                return redirect()->back()->with('error', 'Mobile money request failed: ' . $errorMessage)->withInput();
            }

            // Cash or bank transfer: mark as completed immediately and activate policy
            $policy->update([
                'status' => 'active',
                'is_paid' => true,
                'payment_date' => now(),
            ]);

            // Create Payment record for accounting
            Payment::create([
                'payment_reference' => $paymentReference,
                'invoice_id' => null,
                'policy_id' => $policy->id,
                'client_id' => $client->id,
                'payment_type' => 'premium_payment',
                'amount' => $amount,
                'paid_amount' => $amount,
                'balance_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'mobile_money_number' => null,
                'transaction_id' => null,
                'status' => 'completed',
                'payment_date' => now(),
                'processed_at' => now(),
                'payment_notes' => $validated['notes'] ?? 'Premium payment (cash/bank)',
                'processed_by' => $user->id,
            ]);

            DB::commit();

            return redirect()->route('clients.show', $client)
                ->with('success', 'Premium paid successfully. Policy is now active.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Premium payment error', [
                'client_id' => $client->id,
                'policy_id' => $policy->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }
}
