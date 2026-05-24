<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClientAccountStatementService
{
    /**
     * @param  array<int>  $activityClientIds
     * @return array{
     *     available_balance: float,
     *     total_balance: float,
     *     total_credits: float,
     *     total_debits: float
     * }
     */
    public function summaryBalances(array $activityClientIds, ?ClientAccount $account): array
    {
        $totals = Transaction::query()
            ->whereIn('client_id', $activityClientIds)
            ->selectRaw('COALESCE(SUM(credit_amount), 0) as sum_credits, COALESCE(SUM(debit_amount), 0) as sum_debits')
            ->first();

        $totalCredits = (float) ($totals->sum_credits ?? 0);
        $totalDebits = (float) ($totals->sum_debits ?? 0);
        $availableBalance = $totalCredits - $totalDebits;

        if ($account && abs($availableBalance - (float) $account->available_balance) < 0.01) {
            $availableBalance = (float) $account->available_balance;
        }

        return [
            'available_balance' => $availableBalance,
            'total_balance' => $availableBalance,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
        ];
    }

    /**
     * Compute running available balance per transaction (chronological), attach to paginated rows.
     *
     * @param  array<int>  $activityClientIds
     */
    public function enrichTransactions(LengthAwarePaginator $transactions, array $activityClientIds): LengthAwarePaginator
    {
        $chronological = Transaction::query()
            ->whereIn('client_id', $activityClientIds)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['id', 'credit_amount', 'debit_amount', 'balance_after']);

        $running = 0.0;
        $runningById = [];

        foreach ($chronological as $transaction) {
            if ($transaction->balance_after !== null) {
                $running = (float) $transaction->balance_after;
            } else {
                $running += (float) $transaction->credit_amount - (float) $transaction->debit_amount;
            }

            $runningById[$transaction->id] = $running;
        }

        $transactions->getCollection()->transform(function (Transaction $transaction) use ($runningById) {
            $available = $runningById[$transaction->id] ?? (float) ($transaction->balance_after ?? 0);
            $transaction->setAttribute('available_balance_after', $available);
            $transaction->setAttribute('total_balance_after', $available);

            return $transaction;
        });

        return $transactions;
    }
}
