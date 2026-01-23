<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\InsuranceCompany;
use App\Models\User;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PreAuthorization;
use App\Models\PolicyBenefit;
use App\Models\PaymentResponsibility;
use App\Models\AuthorizationItem;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean the database, keeping only the admin business (id == 1) and its related data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete all data except the admin business (id == 1). Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting database cleanup...');
        
        try {
            DB::beginTransaction();

            // Check if insurance company with id == 1 exists
            $adminCompany = InsuranceCompany::find(1);
            if (!$adminCompany) {
                $this->error('Insurance company with id == 1 does not exist!');
                return 1;
            }

            $this->info("Keeping admin business: {$adminCompany->name} (ID: {$adminCompany->id})");

            // Get all insurance company IDs except 1
            $otherCompanyIds = InsuranceCompany::where('id', '!=', 1)->pluck('id')->toArray();
            
            if (empty($otherCompanyIds)) {
                $this->info('No other insurance companies found. Nothing to clean.');
                DB::commit();
                return 0;
            }

            $this->info('Deleting data for ' . count($otherCompanyIds) . ' insurance company(ies)...');

            // Delete in order to respect foreign key constraints
            
            // 1. Delete authorization items (related to pre-authorizations)
            $preAuthIds = PreAuthorization::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->pluck('id');
            
            AuthorizationItem::whereIn('pre_authorization_id', $preAuthIds)->delete();
            $this->info('  ✓ Deleted authorization items');

            // 2. Delete transactions (related to policies, clients, invoices, payments)
            Transaction::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->orWhereIn('client_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('clients')
                    ->whereIn('plan_id', function($q) use ($otherCompanyIds) {
                        $q->select('id')
                            ->from('plans')
                            ->whereIn('insurance_company_id', $otherCompanyIds);
                    });
            })->delete();
            $this->info('  ✓ Deleted transactions');

            // 3. Delete payment responsibilities
            PaymentResponsibility::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->delete();
            $this->info('  ✓ Deleted payment responsibilities');

            // 4. Delete pre-authorizations
            PreAuthorization::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->delete();
            $this->info('  ✓ Deleted pre-authorizations');

            // 5. Delete policy benefits
            PolicyBenefit::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->delete();
            $this->info('  ✓ Deleted policy benefits');

            // 6. Delete invoices
            Invoice::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->delete();
            $this->info('  ✓ Deleted invoices');

            // 7. Delete payments
            Payment::whereIn('policy_id', function($query) use ($otherCompanyIds) {
                $query->select('id')
                    ->from('policies')
                    ->whereIn('insurance_company_id', $otherCompanyIds);
            })->delete();
            $this->info('  ✓ Deleted payments');

            // 8. Delete policies
            $deletedPolicies = Policy::whereIn('insurance_company_id', $otherCompanyIds)->delete();
            $this->info("  ✓ Deleted {$deletedPolicies} policies");

            // 9. Delete clients (related to plans)
            $planIds = Plan::whereIn('insurance_company_id', $otherCompanyIds)->pluck('id')->toArray();
            $deletedClients = Client::whereIn('plan_id', $planIds)->delete();
            $this->info("  ✓ Deleted {$deletedClients} clients");

            // 10. Delete plan-service_category pivot records
            DB::table('plan_service_category')
                ->whereIn('plan_id', $planIds)
                ->delete();
            $this->info('  ✓ Deleted plan-service category relationships');

            // 11. Delete plans
            $deletedPlans = Plan::whereIn('insurance_company_id', $otherCompanyIds)->delete();
            $this->info("  ✓ Deleted {$deletedPlans} plans");

            // 12. Delete users (except those with insurance_company_id == 1 or id == 1)
            $deletedUsers = User::where(function($query) use ($otherCompanyIds) {
                $query->whereIn('insurance_company_id', $otherCompanyIds)
                      ->orWhere(function($q) use ($otherCompanyIds) {
                          $q->whereNotNull('insurance_company_id')
                            ->whereIn('insurance_company_id', $otherCompanyIds);
                      });
            })->where('id', '!=', 1)->delete();
            $this->info("  ✓ Deleted {$deletedUsers} users");

            // 13. Delete insurance companies (except id == 1)
            $deletedCompanies = InsuranceCompany::whereIn('id', $otherCompanyIds)->delete();
            $this->info("  ✓ Deleted {$deletedCompanies} insurance companies");

            // 14. Clean up password reset tokens for deleted users (optional)
            // This is handled automatically by foreign key constraints or we can leave them

            DB::commit();

            $this->info('');
            $this->info('✓ Database cleanup completed successfully!');
            $this->info("✓ Kept admin business: {$adminCompany->name} (ID: {$adminCompany->id})");
            
            // Show remaining counts
            $remainingUsers = User::where('insurance_company_id', 1)->orWhere('id', 1)->count();
            $remainingPlans = Plan::where('insurance_company_id', 1)->count();
            $remainingPolicies = Policy::where('insurance_company_id', 1)->count();
            $remainingClients = Client::whereIn('plan_id', Plan::where('insurance_company_id', 1)->pluck('id'))->count();
            
            $this->info('');
            $this->info('Remaining data:');
            $this->info("  - Insurance Companies: 1");
            $this->info("  - Users: {$remainingUsers}");
            $this->info("  - Plans: {$remainingPlans}");
            $this->info("  - Policies: {$remainingPolicies}");
            $this->info("  - Clients: {$remainingClients}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during cleanup: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
