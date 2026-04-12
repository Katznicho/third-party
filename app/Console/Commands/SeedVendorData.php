<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\InsuranceCompany;
use App\Models\MedicalQuestion;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\ServiceCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedVendorData extends Command
{
    protected $signature = 'seed:vendor-data {code : The insurance company code}
                            {--clients=5 : Number of dummy clients to create}
                            {--fresh : Delete existing seeded data for this company first}';

    protected $description = 'Seed plans, medical questions, policies and clients for any regular (non-open-enrollment) vendor by code';

    public function handle(): int
    {
        $code = $this->argument('code');
        $clientCount = (int) $this->option('clients');

        $company = InsuranceCompany::where('code', $code)->first();

        if (! $company) {
            $this->error("No insurance company found with code: {$code}");
            return 1;
        }

        $this->info("Vendor: {$company->name} (ID: {$company->id}, Code: {$company->code})");
        $this->newLine();

        if ($this->option('fresh')) {
            $this->cleanExisting($company);
        }

        $this->seedPlans($company);
        $this->newLine();
        $this->seedMedicalQuestions($company);
        $this->newLine();
        $this->seedClients($company, $clientCount);

        $this->newLine();
        $this->info("Done. Run in Kashtre: php artisan seed:vendor-clients {$code}");
        return 0;
    }

    private function cleanExisting(InsuranceCompany $company): void
    {
        $this->warn('--fresh: removing existing seeded data...');

        // Remove policies and clients whose policy numbers match seed pattern
        $prefix = $this->policyPrefix($company->code);
        $policies = Policy::where('insurance_company_id', $company->id)
            ->where('policy_number', 'like', "{$prefix}-%")
            ->get();

        foreach ($policies as $policy) {
            if ($policy->principal_member_id) {
                ClientAccount::where('client_id', $policy->principal_member_id)->delete();
                Client::where('id', $policy->principal_member_id)->delete();
            }
            $policy->delete();
        }

        MedicalQuestion::where('insurance_company_id', $company->id)->delete();
        Plan::where('insurance_company_id', $company->id)->delete();

        $this->info('  Cleaned.');
    }

    private function seedPlans(InsuranceCompany $company): void
    {
        $this->info('Creating plans...');

        $tiers = [
            ['name' => 'Prestige',     'code' => 'PRE', 'sort' => 1, 'copay' => 30000, 'deductible' => 500000, 'coinsurance' => 10, 'balance' => 5000000],
            ['name' => 'Executive',    'code' => 'EXE', 'sort' => 2, 'copay' => 25000, 'deductible' => 300000, 'coinsurance' => 15, 'balance' => 3000000],
            ['name' => 'Standard Plus','code' => 'STP', 'sort' => 3, 'copay' => 20000, 'deductible' => 200000, 'coinsurance' => 20, 'balance' => 2000000],
            ['name' => 'Standard',     'code' => 'STD', 'sort' => 4, 'copay' => 15000, 'deductible' => 150000, 'coinsurance' => 20, 'balance' => 1500000],
            ['name' => 'Regular',      'code' => 'REG', 'sort' => 5, 'copay' => 10000, 'deductible' => 100000, 'coinsurance' => 25, 'balance' => 800000],
            ['name' => 'Budget',       'code' => 'BUD', 'sort' => 6, 'copay' => 5000,  'deductible' => 50000,  'coinsurance' => 30, 'balance' => 400000],
        ];

        $categories = ServiceCategory::whereIn('name', ['Inpatient', 'Outpatient', 'Maternity', 'Optical', 'Dental'])
            ->where('is_active', true)
            ->get()
            ->keyBy('name');

        foreach ($tiers as $tier) {
            $exists = Plan::where('insurance_company_id', $company->id)
                ->where('code', $tier['code'])
                ->exists();

            if ($exists) {
                $this->line("  skip {$tier['name']} (already exists)");
                continue;
            }

            $slug = Str::slug($tier['name'] . '-' . $company->id);
            $plan = Plan::create([
                'name'                              => $tier['name'],
                'slug'                              => $slug,
                'code'                              => $tier['code'],
                'description'                       => "{$tier['name']} plan for {$company->name}",
                'insurance_company_id'              => $company->id,
                'is_active'                         => true,
                'sort_order'                        => $tier['sort'],
                'min_enrollment_age'                => 18,
                'max_enrollment_age'                => 70,
                'dependent_coverage_multiplier'     => 0.50,
                'premium_calculation_method'        => 'benefit_based',
                'insurance_training_levy_percentage'=> 0.50,
                'stamp_duty_amount'                 => 35000,
                'base_premium'                      => $tier['balance'] * 0.05,
            ]);

            // Attach service categories
            $sync = [];
            $multipliers = ['Inpatient' => 10, 'Outpatient' => 1, 'Maternity' => 2, 'Optical' => 0.3, 'Dental' => 0.3];
            foreach ($multipliers as $catName => $mult) {
                $cat = $categories->get($catName);
                if ($cat) {
                    $sync[$cat->id] = [
                        'benefit_amount'     => (int) ($tier['balance'] * $mult),
                        'base_amount'        => (int) ($tier['balance'] * $mult * 0.05),
                        'waiting_period_days'=> $catName === 'Maternity' ? 365 : 0,
                        'is_enabled'         => true,
                    ];
                }
            }
            $plan->serviceCategories()->sync($sync);

            $this->info("  + {$tier['name']} ({$tier['code']})");
        }
    }

    private function seedMedicalQuestions(InsuranceCompany $company): void
    {
        $this->info('Creating medical questions...');

        $questions = [
            ['text' => 'Do you have any pre-existing medical conditions?',        'type' => 'yes_no', 'has_exclusion' => true,  'exclusion_keywords' => ['cancer', 'hiv', 'diabetes', 'hypertension'], 'order' => 1],
            ['text' => 'Are you currently on any chronic medication?',            'type' => 'yes_no', 'has_exclusion' => false, 'exclusion_keywords' => [],                                               'order' => 2],
            ['text' => 'Have you been hospitalised in the last 12 months?',       'type' => 'yes_no', 'has_exclusion' => true,  'exclusion_keywords' => ['surgery', 'transplant', 'dialysis'],          'order' => 3],
            ['text' => 'Do you smoke or use tobacco products?',                   'type' => 'yes_no', 'has_exclusion' => false, 'exclusion_keywords' => [],                                               'order' => 4],
            ['text' => 'Are you pregnant or planning to become pregnant?',        'type' => 'yes_no', 'has_exclusion' => false, 'exclusion_keywords' => [],                                               'order' => 5],
        ];

        $existing = MedicalQuestion::where('insurance_company_id', $company->id)->count();
        if ($existing > 0) {
            $this->line("  skip ({$existing} questions already exist)");
            return;
        }

        foreach ($questions as $q) {
            MedicalQuestion::create([
                'insurance_company_id'  => $company->id,
                'question_text'         => $q['text'],
                'question_type'         => $q['type'],
                'has_exclusion_list'    => $q['has_exclusion'],
                'exclusion_keywords'    => $q['exclusion_keywords'],
                'requires_additional_info' => false,
                'order'                 => $q['order'],
                'is_active'             => true,
                'has_monetary_impact'   => false,
            ]);
            $this->info("  + Q{$q['order']}: {$q['text']}");
        }
    }

    private function seedClients(InsuranceCompany $company, int $count): void
    {
        $this->info("Creating {$count} dummy clients with policies and accounts...");

        $prefix = $this->policyPrefix($company->code);
        $year   = now()->year;

        // Get the Standard plan for these clients
        $plan = Plan::where('insurance_company_id', $company->id)
            ->where('code', 'STD')
            ->orWhere(function ($q) use ($company) {
                $q->where('insurance_company_id', $company->id)->where('is_active', true);
            })
            ->first();

        $dummies = [
            ['surname' => 'Nakato',    'first_name' => 'Sarah',  'gender' => 'Female', 'dob' => '1990-05-14', 'copay' => 15000, 'deductible' => 200000, 'coinsurance' => 20, 'balance' => 1500000],
            ['surname' => 'Okello',    'first_name' => 'James',  'gender' => 'Male',   'dob' => '1985-03-22', 'copay' => 20000, 'deductible' => 300000, 'coinsurance' => 10, 'balance' => 2000000],
            ['surname' => 'Tumukunde', 'first_name' => 'Grace',  'gender' => 'Female', 'dob' => '1978-11-08', 'copay' => 10000, 'deductible' => 150000, 'coinsurance' => 15, 'balance' => 800000],
            ['surname' => 'Mugisha',   'first_name' => 'Brian',  'gender' => 'Male',   'dob' => '1995-07-30', 'copay' => 25000, 'deductible' => 500000, 'coinsurance' => 20, 'balance' => 3000000],
            ['surname' => 'Apio',      'first_name' => 'Faith',  'gender' => 'Female', 'dob' => '1988-01-19', 'copay' => 12000, 'deductible' => 250000, 'coinsurance' => 10, 'balance' => 1200000],
            ['surname' => 'Ssemakula', 'first_name' => 'Robert', 'gender' => 'Male',   'dob' => '1975-09-03', 'copay' => 30000, 'deductible' => 500000, 'coinsurance' => 10, 'balance' => 5000000],
            ['surname' => 'Nabirye',   'first_name' => 'Lydia',  'gender' => 'Female', 'dob' => '1992-12-25', 'copay' => 8000,  'deductible' => 100000, 'coinsurance' => 25, 'balance' => 600000],
            ['surname' => 'Mwesigwa',  'first_name' => 'Peter',  'gender' => 'Male',   'dob' => '1983-06-17', 'copay' => 18000, 'deductible' => 250000, 'coinsurance' => 15, 'balance' => 1800000],
        ];

        $dummies = array_slice($dummies, 0, $count);

        foreach ($dummies as $i => $d) {
            $seq    = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $policyNum = "{$prefix}-{$year}-{$seq}";

            // Skip if already exists
            if (Policy::where('policy_number', $policyNum)->exists()) {
                $this->line("  skip {$policyNum} (already exists)");
                continue;
            }

            $member = Client::create([
                'type'                 => 'principal',
                'surname'              => $d['surname'],
                'first_name'           => $d['first_name'],
                'gender'               => $d['gender'],
                'date_of_birth'        => $d['dob'],
                'insurance_company_id' => $company->id,
                'plan_id'              => $plan?->id,
                'cell_phone'           => '+25670100000' . ($i + 1),
                'email'                => strtolower($d['first_name'] . '.' . $d['surname']) . '@' . Str::slug($company->name) . '.test',
                'nationality'          => 'Ugandan',
                'is_active'            => true,
            ]);

            $policy = Policy::create([
                'policy_number'                      => $policyNum,
                'insurance_company_id'               => $company->id,
                'principal_member_id'                => $member->id,
                'plan_type'                          => 'Standard',
                'inception_date'                     => now()->startOfYear(),
                'expiry_date'                        => now()->addYear()->endOfYear(),
                'total_premium'                      => $d['balance'] * 0.05,
                'insurance_training_levy'             => $d['balance'] * 0.05 * 0.005,
                'stamp_duty'                         => 35000,
                'total_premium_due'                  => ($d['balance'] * 0.05) + ($d['balance'] * 0.05 * 0.005) + 35000,
                'status'                             => 'active',
                'is_paid'                            => true,
                'payment_date'                       => now(),
                'has_deductible'                     => true,
                'copay_amount'                       => $d['copay'],
                'deductible_amount'                  => $d['deductible'],
                'coinsurance_percentage'             => $d['coinsurance'],
                'copay_contributes_to_deductible'    => false,
                'coinsurance_contributes_to_deductible' => true,
            ]);

            ClientAccount::create([
                'client_id'          => $member->id,
                'insurance_company_id' => $company->id,
                'account_number'     => 'ACC-' . $policyNum,
                'account_type'       => 'individual',
                'status'             => 'active',
                'opening_balance'    => $d['balance'],
                'current_balance'    => $d['balance'],
                'available_balance'  => $d['balance'],
                'opened_date'        => now()->startOfYear(),
            ]);

            $this->info("  + {$d['surname']} {$d['first_name']} → {$policyNum} | copay: " . number_format($d['copay']) . " | deductible: " . number_format($d['deductible']) . " | coinsurance: {$d['coinsurance']}%");
        }
    }

    private function policyPrefix(string $code): string
    {
        // Take first 3 alphanumeric chars of the code, uppercase
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($code, 0, 3)));
    }
}
