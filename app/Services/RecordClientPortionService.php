<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\InsuranceCompany;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecordClientPortionService
{
    /**
     * Record a client-portion payment: create Payment, Transaction, update ClientAccount.
     * Same logic as API record-client-portion. Records appear in:
     * - /payments
     * - /clients/{id}/account-statement (and /balance-statement/{id})
     * - /third-party-vendors/{id} (when connected_business_id is set)
     *
     * @param array $validated Must include: insurance_company_id, policy_number, amount, payment_reference.
     *                         Optional: payment_method, mobile_money_number, kashtre_invoice_id, authorization_reference, connected_business_id.
     * @return array{success: bool, payment?: Payment, transaction?: Transaction, client_id?: int, message?: string}
     */
    public static function record(array $validated): array
    {
        $validated['payment_method'] = $validated['payment_method'] ?? 'mobile_money';
        $validated['payment_date'] = $validated['payment_date'] ?? now()->format('Y-m-d');
        $validated['status'] = 'completed';

        $policy = Policy::where('insurance_company_id', $validated['insurance_company_id'])
            ->where('policy_number', trim($validated['policy_number']))
            ->with('principalMember')
            ->first();

        if (!$policy) {
            Log::warning('[RecordClientPortionService] Policy not found', [
                'insurance_company_id' => $validated['insurance_company_id'],
                'policy_number' => $validated['policy_number'],
            ]);
            return ['success' => false, 'message' => 'Policy not found for this insurance company and policy number.'];
        }

        $client = $policy->principalMember;
        if (!$client) {
            return ['success' => false, 'message' => 'No principal member found for this policy.'];
        }

        try {
            DB::beginTransaction();

            $paymentData = [
                'payment_reference' => $validated['payment_reference'],
                'invoice_id' => null,
                'client_id' => $client->id,
                'policy_id' => $policy->id,
                'payment_type' => 'full_payment',
                'amount' => $validated['amount'],
                'paid_amount' => $validated['amount'],
                'balance_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_id' => null,
                'status' => $validated['status'],
                'payment_date' => $validated['payment_date'],
                'received_date' => now(),
                'payment_notes' => $validated['payment_notes'] ?? ('Client portion collected. Ref: ' . ($validated['authorization_reference'] ?? $validated['kashtre_invoice_id'] ?? $validated['payment_reference'])),
                'payment_metadata' => array_filter([
                    'source' => $validated['source'] ?? 'kashtre',
                    'kashtre_invoice_id' => $validated['kashtre_invoice_id'] ?? null,
                    'authorization_reference' => $validated['authorization_reference'] ?? null,
                    'client_portion' => true,
                    'insurance_company_id' => $validated['insurance_company_id'],
                    'connected_business_id' => $validated['connected_business_id'] ?? null,
                ], fn ($v) => $v !== null),
                'processed_by' => $validated['processed_by'] ?? null,
            ];

            $payment = Payment::create($paymentData);

            $account = ClientAccount::where('client_id', $client->id)
                ->where('insurance_company_id', $validated['insurance_company_id'])
                ->first();

            if (!$account) {
                $insuranceCompany = InsuranceCompany::find($validated['insurance_company_id']);
                if ($insuranceCompany) {
                    $accountNumber = ClientAccount::generateAccountNumber($insuranceCompany);
                    $account = ClientAccount::create([
                        'client_id' => $client->id,
                        'insurance_company_id' => $validated['insurance_company_id'],
                        'account_number' => $accountNumber,
                        'account_type' => 'individual',
                        'status' => 'active',
                        'opening_balance' => 0,
                        'current_balance' => 0,
                        'total_debits' => 0,
                        'total_credits' => 0,
                        'available_balance' => 0,
                        'opened_date' => now(),
                    ]);
                }
            }

            $transaction = null;
            if ($account) {
                $balanceBefore = (float) ($account->current_balance ?? 0);
                $balanceAfter = $balanceBefore + (float) $validated['amount'];

                $transaction = Transaction::create([
                    'client_id' => $client->id,
                    'policy_id' => $policy->id,
                    'invoice_id' => null,
                    'payment_id' => $payment->id,
                    'type' => 'copayment',
                    'transaction_date' => now(),
                    'transaction_number' => 'TXN-' . strtoupper(Str::random(8)) . '-' . time(),
                    'description' => 'Client portion payment',
                    'reference_number' => $validated['payment_reference'],
                    'amount' => $validated['amount'],
                    'debit_amount' => 0,
                    'credit_amount' => $validated['amount'],
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'transaction_status' => 'cleared',
                    'payment_method' => $validated['payment_method'],
                    'service_category_id' => null,
                ]);

                $account->update([
                    'current_balance' => $balanceAfter,
                    'total_credits' => ($account->total_credits ?? 0) + $validated['amount'],
                    'available_balance' => $balanceAfter,
                    'last_transaction_date' => now(),
                ]);

                Log::info('[RecordClientPortionService] Client portion recorded', [
                    'payment_id' => $payment->id,
                    'client_id' => $client->id,
                    'amount' => $validated['amount'],
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment,
                'transaction' => $transaction,
                'client_id' => $client->id,
                'message' => 'Payment recorded. It will appear in Payments, client account statement, balance statement, and third-party vendor page.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[RecordClientPortionService] Failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
