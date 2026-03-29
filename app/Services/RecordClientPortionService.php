<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\InsuranceAuthorization;
use App\Models\InsuranceCompany;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\PolicyDeductibleLedger;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecordClientPortionService
{
    /**
     * Record a client-portion payment. This single action updates all 4 statements:
     *
     * 1. /payments                          – new row (we create Payment)
     * 2. /clients/{id}/account-statement    – new transaction + balance (we create Transaction, update ClientAccount)
     * 3. /balance-statement/{id}            – same data as (2), redirects to account-statement
     * 4. /third-party-vendors/{id}          – new row (Payment has payment_metadata.source = 'kashtre')
     *
     * Policy deductible ledger: new rows are appended only here, only when recording a completed client-portion
     * payment (not at invoice authorization). Nothing before the client pays their portion updates that ledger.
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
                    'invoice_number' => $validated['invoice_number'] ?? null,
                    'authorization_reference' => $validated['authorization_reference'] ?? null,
                    'client_portion' => true,
                    'insurance_company_id' => $validated['insurance_company_id'],
                    'connected_business_id' => $validated['connected_business_id'] ?? null,
                ], fn ($v) => $v !== null),
                'processed_by' => $validated['processed_by'] ?? null,
            ];

            $payment = Payment::create($paymentData);

            self::recordDeductibleLedgerAfterClientPayment($validated, $policy);

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

    /**
     * Sole write path for policy_deductible_ledgers: after a successful client-portion payment is recorded.
     * Amounts are copied from InsuranceAuthorization metadata (computed at invoice save; ledger still unchanged until this runs).
     */
    private static function recordDeductibleLedgerAfterClientPayment(array $validated, Policy $policy): void
    {
        if (($validated['status'] ?? '') !== 'completed') {
            return;
        }

        $kashtreInvoiceId = $validated['kashtre_invoice_id'] ?? null;
        $authRef = $validated['authorization_reference'] ?? null;
        if ($kashtreInvoiceId === null && $authRef === null) {
            return;
        }

        $query = InsuranceAuthorization::where('insurance_company_id', $validated['insurance_company_id'])
            ->where('policy_id', $policy->id);
        if ($kashtreInvoiceId !== null && $kashtreInvoiceId !== '') {
            $query->where('kashtre_invoice_id', (string) $kashtreInvoiceId);
        }
        if ($authRef !== null && $authRef !== '') {
            $query->where('authorization_reference', $authRef);
        }

        $auth = $query->first();
        if (!$auth) {
            Log::info('[RecordClientPortionService] No authorization match for deductible ledger', [
                'kashtre_invoice_id' => $kashtreInvoiceId,
                'authorization_reference' => $authRef,
                'policy_id' => $policy->id,
            ]);

            return;
        }

        if (PolicyDeductibleLedger::where('authorization_id', $auth->id)->exists()) {
            return;
        }

        $meta = $auth->metadata ?? [];
        $amountReduces = (float) ($meta['amount_that_reduces_deductible'] ?? 0);
        if ($amountReduces <= 0) {
            return;
        }

        $deductibleBefore = (float) ($meta['deductible_remaining_before'] ?? 0);
        $deductibleAfter = (float) ($meta['deductible_remaining_after'] ?? max(0, $deductibleBefore - $amountReduces));

        PolicyDeductibleLedger::create([
            'insurance_company_id' => $validated['insurance_company_id'],
            'policy_id' => $policy->id,
            'authorization_id' => $auth->id,
            'kashtre_invoice_id' => $auth->kashtre_invoice_id,
            'external_invoice_number' => $auth->external_invoice_number,
            'change_type' => 'invoice',
            'deductible_before' => $deductibleBefore,
            'amount_that_reduces_deductible' => $amountReduces,
            'deductible_after' => $deductibleAfter,
            'notes' => 'Recorded when Kashtre confirmed client portion payment.',
        ]);

        Log::info('[RecordClientPortionService] Policy deductible ledger recorded after client payment', [
            'authorization_id' => $auth->id,
            'policy_id' => $policy->id,
            'amount_that_reduces_deductible' => $amountReduces,
        ]);
    }
}
