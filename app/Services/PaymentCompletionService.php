<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\InsuranceCompany;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentCompletionService
{
    /**
     * When a payment is marked completed, credit all 4 accounts:
     * 1. Payment record (paid_amount, balance_amount=0)
     * 2. ClientAccount (current_balance, total_credits, available_balance)
     * 3. Transaction (new row on client account for statement)
     * 4. Policy (when premium: status=active, is_paid=true, payment_date)
     * Idempotent: skips creating a duplicate transaction if one already exists for this payment.
     */
    public static function ensureTransactionAndAccountForCompletedPayment(Payment $payment): ?Transaction
    {
        if ($payment->status !== 'completed') {
            return null;
        }

        if (Transaction::where('payment_id', $payment->id)->exists()) {
            return Transaction::where('payment_id', $payment->id)->first();
        }

        $payment->load(['policy', 'client']);
        $policy = $payment->policy;
        $client = $payment->client_id ? $payment->client : null;

        if (!$policy || !$client) {
            Log::warning('[PaymentCompletionService] Cannot create transaction: payment missing policy or client', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
            ]);
            return null;
        }

        $insuranceCompanyId = $policy->insurance_company_id;
        $account = ClientAccount::where('client_id', $client->id)
            ->where('insurance_company_id', $insuranceCompanyId)
            ->first();

        if (!$account) {
            $insuranceCompany = InsuranceCompany::find($insuranceCompanyId);
            if (!$insuranceCompany) {
                Log::warning('[PaymentCompletionService] Insurance company not found', ['id' => $insuranceCompanyId]);
                return null;
            }
            $accountNumber = ClientAccount::generateAccountNumber($insuranceCompany);
            $account = ClientAccount::create([
                'client_id' => $client->id,
                'insurance_company_id' => $insuranceCompanyId,
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

        $amount = (float) $payment->paid_amount ?: (float) $payment->amount;
        $balanceBefore = (float) ($account->current_balance ?? 0);
        $balanceAfter = $balanceBefore + $amount;

        $transactionType = self::transactionTypeForPaymentType($payment->payment_type);
        $description = self::descriptionForPaymentType($payment->payment_type);

        $transaction = Transaction::create([
            'client_id' => $client->id,
            'policy_id' => $policy->id,
            'invoice_id' => $payment->invoice_id,
            'payment_id' => $payment->id,
            'type' => $transactionType,
            'transaction_date' => $payment->payment_date ?? now(),
            'transaction_number' => 'TXN-' . strtoupper(Str::random(8)) . '-' . time(),
            'description' => $description,
            'reference_number' => $payment->payment_reference,
            'amount' => $amount,
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_status' => 'cleared',
            'payment_method' => $payment->payment_method ?? 'mobile_money',
            'service_category_id' => null,
        ]);

        // 2. Credit ClientAccount
        $account->update([
            'current_balance' => $balanceAfter,
            'total_credits' => ($account->total_credits ?? 0) + $amount,
            'available_balance' => $balanceAfter,
            'last_transaction_date' => now(),
        ]);

        // 4. Credit Policy (when premium payment) – mark policy paid and active
        if ($payment->payment_type === 'premium_payment' && $policy->id) {
            $policy->update([
                'status' => 'active',
                'is_paid' => true,
                'payment_date' => $payment->payment_date ?? now(),
            ]);
        }

        // 1. Ensure Payment record is fully credited (in case caller didn't set paid_amount/balance_amount)
        $payment->update([
            'paid_amount' => $payment->paid_amount ?: $amount,
            'balance_amount' => 0,
        ]);

        Log::info('[PaymentCompletionService] All 4 accounts credited for completed payment', [
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'client_id' => $client->id,
            'account_id' => $account->id,
            'policy_updated' => $payment->payment_type === 'premium_payment',
            'amount' => $amount,
        ]);

        return $transaction;
    }

    protected static function transactionTypeForPaymentType(string $paymentType): string
    {
        return match ($paymentType) {
            'premium_payment' => 'premium_payment',
            'full_payment', 'partial_payment' => 'copayment',
            default => 'credit',
        };
    }

    protected static function descriptionForPaymentType(string $paymentType): string
    {
        return match ($paymentType) {
            'premium_payment' => 'Premium payment',
            'full_payment', 'partial_payment' => 'Client portion / co-payment',
            default => 'Payment received',
        };
    }
}
