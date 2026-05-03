<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearOrderDataCommand extends Command
{
    protected $signature = 'testing:clear-order-data
        {--confirm : Required; prevents accidental runs}
        {--full : Remove pre-auth chain, visits, authorizations, and optional claim/service invoices (destructive)}
        {--keep-balances : With --full only: do not zero client_accounts balances}
        {--with-service-invoices : With --full only: also delete service/claim invoices and linked payments/transactions}';

    protected $description = 'Default: clears only transient visit verification rows. Use --full for pre-authorizations, audits, authorizations, visits (see options). medical_questions are never deleted.';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Refusing to run without --confirm.');

            return 1;
        }

        if ($this->option('with-service-invoices') && ! $this->option('full')) {
            $this->error('--with-service-invoices requires --full.');

            return 1;
        }

        if ($this->option('keep-balances') && ! $this->option('full')) {
            $this->error('--keep-balances is only used with --full.');

            return 1;
        }

        if (! $this->option('full')) {
            $this->lightReset();

            return 0;
        }

        $this->fullReset();

        if (! $this->option('keep-balances') && Schema::hasTable('client_accounts')) {
            $cols = ['current_balance', 'available_balance', 'total_debits', 'total_credits'];
            $hasAll = collect($cols)->every(fn ($c) => Schema::hasColumn('client_accounts', $c));
            if ($hasAll) {
                $u = DB::table('client_accounts')->update([
                    'current_balance' => 0,
                    'available_balance' => 0,
                    'total_debits' => 0,
                    'total_credits' => 0,
                ]);
                $this->info("Reset all client_accounts balances ({$u} row(s)).");
            }
        } elseif ($this->option('keep-balances')) {
            $this->warn('Skipped client_accounts balance reset.');
        }

        $this->info('Done (full). medical_questions were not modified.');

        return 0;
    }

    /**
     * Only session-like visit verification state — safe to clear between tests without touching policies or premiums.
     */
    private function lightReset(): void
    {
        $this->warn('Light reset: visit_identity_verifications only (policies, clients, pre-auths, invoices unchanged).');

        $this->deleteAllIfExists('visit_identity_verifications');

        $this->info('Done (light).');
    }

    private function fullReset(): void
    {
        $this->warn('Full reset: clearing authorization and visit operational data.');

        DB::transaction(function () {
            $this->deleteAllIfExists('rejected_items');
            $this->deleteAllIfExists('policy_deductible_ledgers');
            $this->deleteAllIfExists('authorization_audit_logs');
            $this->deleteAllIfExists('pre_authorization_approvals');

            if (Schema::hasTable('payment_responsibilities') && Schema::hasColumn('payment_responsibilities', 'pre_authorization_id')) {
                $n = DB::table('payment_responsibilities')->whereNotNull('pre_authorization_id')->delete();
                $this->info("Deleted payment_responsibilities tied to pre-authorization ({$n} row(s)).");
            }

            if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'pre_authorization_id')) {
                $n = DB::table('transactions')->whereNotNull('pre_authorization_id')->delete();
                $this->info("Deleted transactions tied to pre-authorization ({$n} row(s)).");
            }

            $this->deleteAllIfExists('authorization_items');
            $this->deleteAllIfExists('pre_authorizations');
            $this->deleteAllIfExists('insurance_authorizations');
            $this->deleteAllIfExists('authorized_visits');
            $this->deleteAllIfExists('visit_identity_verifications');

            if ($this->option('with-service-invoices') && Schema::hasTable('invoices')) {
                $ids = DB::table('invoices')->whereIn('invoice_type', ['service', 'claim'])->pluck('id');
                if ($ids->isNotEmpty()) {
                    if (Schema::hasTable('payments')) {
                        $p = DB::table('payments')->whereIn('invoice_id', $ids)->delete();
                        $this->info("Deleted payments for service/claim invoices ({$p} row(s)).");
                    }
                    if (Schema::hasTable('transactions')) {
                        $t = DB::table('transactions')->whereIn('invoice_id', $ids)->delete();
                        $this->info("Deleted transactions for service/claim invoices ({$t} row(s)).");
                    }
                    $inv = DB::table('invoices')->whereIn('id', $ids)->delete();
                    $this->info("Deleted service/claim invoices ({$inv} row(s)).");
                }
            }
        });
    }

    private function deleteAllIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->warn("Skip missing table: {$table}");

            return;
        }
        $n = DB::table($table)->count();
        DB::table($table)->delete();
        $this->info("Cleared {$table} ({$n} rows).");
    }
}
