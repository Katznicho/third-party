<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearOrderDataCommand extends Command
{
    protected $signature = 'testing:clear-order-data
        {--confirm : Required; prevents accidental runs}
        {--keep-balances : Do not zero client_accounts balances after clearing}
        {--with-service-invoices : Also remove invoices of type service or claim and their payments/transactions (premium invoices are kept)}';

    protected $description = 'Remove visit / pre-authorization / insurance authorization data only. Does not delete medical_questions, plans, or premium billing unless --with-service-invoices is used for claim/service invoices.';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Refusing to run without --confirm.');

            return 1;
        }

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

        $this->info('Done. medical_questions were not modified.');

        return 0;
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
