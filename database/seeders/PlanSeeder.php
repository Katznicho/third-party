<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\ServiceCategory;
use App\Models\InsuranceCompany;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all insurance companies
        $insuranceCompanies = InsuranceCompany::all();

        if ($insuranceCompanies->isEmpty()) {
            $this->command->warn('No insurance companies found. Please run InsuranceCompanySeeder first.');
            return;
        }

        // Define plans with their benefit amounts
        $plans = [
            [
                'name' => 'Prestige',
                'code' => 'PRE',
                'sort_order' => 1,
                'benefits' => [
                    'Inpatient' => 200000000,
                    'Outpatient' => 7000000,
                    'Maternity' => 6000000,
                    'Optical' => 1300000,
                    'Dental' => 1300000,
                    'Funeral Expenses' => 3500000,
                    'Hospital Cash' => 50000,
                    'Life Cover' => 100000000,
                ],
            ],
            [
                'name' => 'Executive',
                'code' => 'EXE',
                'sort_order' => 2,
                'benefits' => [
                    'Inpatient' => 100000000,
                    'Outpatient' => 5000000,
                    'Maternity' => 5000000,
                    'Optical' => 1000000,
                    'Dental' => 1000000,
                    'Funeral Expenses' => 3000000,
                    'Hospital Cash' => 40000,
                    'Life Cover' => 80000000,
                ],
            ],
            [
                'name' => 'Standard Plus',
                'code' => 'STD+',
                'sort_order' => 3,
                'benefits' => [
                    'Inpatient' => 60000000,
                    'Outpatient' => 3500000,
                    'Maternity' => 4000000,
                    'Optical' => 650000,
                    'Dental' => 650000,
                    'Funeral Expenses' => 2500000,
                    'Hospital Cash' => 30000,
                    'Life Cover' => 50000000,
                ],
            ],
            [
                'name' => 'Standard',
                'code' => 'STD',
                'sort_order' => 4,
                'benefits' => [
                    'Inpatient' => 30000000,
                    'Outpatient' => 2500000,
                    'Maternity' => 3500000,
                    'Optical' => 400000,
                    'Dental' => 400000,
                    'Funeral Expenses' => 2000000,
                    'Hospital Cash' => 20000,
                    'Life Cover' => null,
                ],
            ],
            [
                'name' => 'Regular',
                'code' => 'REG',
                'sort_order' => 5,
                'benefits' => [
                    'Inpatient' => 15000000,
                    'Outpatient' => 1500000,
                    'Maternity' => 2500000,
                    'Optical' => 350000,
                    'Dental' => 350000,
                    'Funeral Expenses' => 1500000,
                    'Hospital Cash' => 10000,
                    'Life Cover' => null,
                ],
            ],
            [
                'name' => 'Budget',
                'code' => 'BUD',
                'sort_order' => 6,
                'benefits' => [
                    'Inpatient' => 5000000,
                    'Outpatient' => 1000000,
                    'Maternity' => 1500000,
                    'Optical' => 200000,
                    'Dental' => 200000,
                    'Funeral Expenses' => 1000000,
                    'Hospital Cash' => null,
                    'Life Cover' => null,
                ],
            ],
        ];

        foreach ($insuranceCompanies as $insuranceCompany) {
            $this->command->info("Creating plans for {$insuranceCompany->name}...");

            foreach ($plans as $planData) {
                // Check if plan already exists
                $existingPlan = Plan::where('insurance_company_id', $insuranceCompany->id)
                    ->where('code', $planData['code'])
                    ->first();

                if ($existingPlan) {
                    $this->command->warn("Plan {$planData['name']} ({$planData['code']}) already exists for {$insuranceCompany->name}. Skipping...");
                    $plan = $existingPlan;
                } else {
                    $plan = Plan::create([
                        'name' => $planData['name'],
                        'slug' => \Illuminate\Support\Str::slug($planData['name']),
                        'code' => $planData['code'],
                        'description' => "{$planData['name']} health insurance plan",
                        'insurance_company_id' => $insuranceCompany->id,
                        'is_active' => true,
                        'sort_order' => $planData['sort_order'],
                    ]);
                    $this->command->info("Created plan: {$planData['name']} ({$planData['code']})");
                }

                // Attach service categories with benefit amounts
                $syncData = [];
                foreach ($planData['benefits'] as $categoryName => $benefitAmount) {
                    if ($benefitAmount === null) {
                        continue; // Skip null benefits
                    }

                    $serviceCategory = ServiceCategory::where('name', $categoryName)->first();

                    if (!$serviceCategory) {
                        $this->command->warn("Service category '{$categoryName}' not found. Creating it...");
                        $serviceCategory = ServiceCategory::create([
                            'name' => $categoryName,
                            'slug' => \Illuminate\Support\Str::slug($categoryName),
                            'code' => strtoupper(substr($categoryName, 0, 3)),
                            'description' => "{$categoryName} service category",
                            'is_mandatory' => $categoryName === 'Inpatient',
                            'is_active' => true,
                            'sort_order' => $this->getCategorySortOrder($categoryName),
                        ]);
                    }

                    $syncData[$serviceCategory->id] = [
                        'benefit_amount' => $benefitAmount,
                        'copay_percentage' => 0,
                        'deductible_amount' => 0,
                        'waiting_period_days' => $categoryName === 'Maternity' ? 365 : 0,
                        'is_enabled' => true,
                    ];
                }

                // Sync service categories
                $plan->serviceCategories()->sync($syncData);
                $this->command->info("Attached " . count($syncData) . " service categories to {$planData['name']}");
            }
        }

        $this->command->info('Plan seeding completed!');
    }

    /**
     * Get sort order for service category
     */
    private function getCategorySortOrder(string $categoryName): int
    {
        $order = [
            'Inpatient' => 1,
            'Outpatient' => 2,
            'Maternity' => 3,
            'Optical' => 4,
            'Dental' => 5,
            'Funeral Expenses' => 6,
            'Hospital Cash' => 7,
            'Life Cover' => 8,
        ];

        return $order[$categoryName] ?? 99;
    }
}
