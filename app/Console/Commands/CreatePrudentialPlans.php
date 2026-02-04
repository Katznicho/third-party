<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InsuranceCompany;
use App\Models\Plan;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

class CreatePrudentialPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:create-prudential';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create plans for Prudential Assurance Uganda Limited (Code: 56178642)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyCode = '56178642';
        $companyName = 'Prudential Assurance Uganda Limited';

        // Find the insurance company
        $insuranceCompany = InsuranceCompany::where('code', $companyCode)->first();

        if (!$insuranceCompany) {
            $this->error("Insurance company with code '{$companyCode}' not found!");
            $this->info("Please create the company first or check the code.");
            return 1;
        }

        $this->info("Found insurance company: {$insuranceCompany->name} (ID: {$insuranceCompany->id})");
        $this->newLine();

        // Define plans with benefit amounts and base amounts
        $plans = [
            [
                'name' => 'Prestige',
                'code' => 'PRE',
                'description' => 'Premium health insurance plan with comprehensive coverage',
                'sort_order' => 1,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 65,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 200000000, 'base' => 5000000],
                    'Outpatient' => ['benefit' => 7000000, 'base' => 200000],
                    'Maternity' => ['benefit' => 6000000, 'base' => 150000],
                    'Optical' => ['benefit' => 1300000, 'base' => 50000],
                    'Dental' => ['benefit' => 1300000, 'base' => 50000],
                    'Funeral Expenses' => ['benefit' => 3500000, 'base' => 100000],
                ],
            ],
            [
                'name' => 'Executive',
                'code' => 'EXE',
                'description' => 'Executive health insurance plan with excellent coverage',
                'sort_order' => 2,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 65,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 100000000, 'base' => 3000000],
                    'Outpatient' => ['benefit' => 5000000, 'base' => 150000],
                    'Maternity' => ['benefit' => 5000000, 'base' => 120000],
                    'Optical' => ['benefit' => 1000000, 'base' => 40000],
                    'Dental' => ['benefit' => 1000000, 'base' => 40000],
                    'Funeral Expenses' => ['benefit' => 3000000, 'base' => 80000],
                ],
            ],
            [
                'name' => 'Standard Plus',
                'code' => 'STD+',
                'description' => 'Standard Plus health insurance plan with good coverage',
                'sort_order' => 3,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 70,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 60000000, 'base' => 2000000],
                    'Outpatient' => ['benefit' => 3500000, 'base' => 100000],
                    'Maternity' => ['benefit' => 4000000, 'base' => 100000],
                    'Optical' => ['benefit' => 650000, 'base' => 30000],
                    'Dental' => ['benefit' => 650000, 'base' => 30000],
                    'Funeral Expenses' => ['benefit' => 2500000, 'base' => 60000],
                ],
            ],
            [
                'name' => 'Standard',
                'code' => 'STD',
                'description' => 'Standard health insurance plan with basic coverage',
                'sort_order' => 4,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 70,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 30000000, 'base' => 1000000],
                    'Outpatient' => ['benefit' => 2500000, 'base' => 70000],
                    'Maternity' => ['benefit' => 3500000, 'base' => 80000],
                    'Optical' => ['benefit' => 400000, 'base' => 20000],
                    'Dental' => ['benefit' => 400000, 'base' => 20000],
                    'Funeral Expenses' => ['benefit' => 2000000, 'base' => 50000],
                ],
            ],
            [
                'name' => 'Regular',
                'code' => 'REG',
                'description' => 'Regular health insurance plan with essential coverage',
                'sort_order' => 5,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 75,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 15000000, 'base' => 500000],
                    'Outpatient' => ['benefit' => 1500000, 'base' => 50000],
                    'Maternity' => ['benefit' => 2500000, 'base' => 60000],
                    'Optical' => ['benefit' => 350000, 'base' => 15000],
                    'Dental' => ['benefit' => 350000, 'base' => 15000],
                    'Funeral Expenses' => ['benefit' => 1500000, 'base' => 40000],
                ],
            ],
            [
                'name' => 'Budget',
                'code' => 'BUD',
                'description' => 'Budget-friendly health insurance plan with basic coverage',
                'sort_order' => 6,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 75,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 5000000, 'base' => 300000],
                    'Outpatient' => ['benefit' => 1000000, 'base' => 30000],
                    'Maternity' => ['benefit' => 1500000, 'base' => 40000],
                    'Optical' => ['benefit' => 200000, 'base' => 10000],
                    'Dental' => ['benefit' => 200000, 'base' => 10000],
                    'Funeral Expenses' => ['benefit' => 1000000, 'base' => 30000],
                ],
            ],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($plans as $planData) {
            // Check if plan already exists
            $existingPlan = Plan::where('insurance_company_id', $insuranceCompany->id)
                ->where('code', $planData['code'])
                ->first();

            if ($existingPlan) {
                $this->warn("Plan {$planData['name']} ({$planData['code']}) already exists. Skipping...");
                $skippedCount++;
                continue;
            }

            // Generate unique slug
            $baseSlug = Str::slug($planData['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Plan::where('slug', $slug)
                ->where('insurance_company_id', $insuranceCompany->id)
                ->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create plan
            $plan = Plan::create([
                'name' => $planData['name'],
                'slug' => $slug,
                'code' => $planData['code'],
                'description' => $planData['description'],
                'insurance_company_id' => $insuranceCompany->id,
                'is_active' => true,
                'sort_order' => $planData['sort_order'],
                'min_enrollment_age' => $planData['min_enrollment_age'],
                'max_enrollment_age' => $planData['max_enrollment_age'],
                'dependent_coverage_multiplier' => $planData['dependent_coverage_multiplier'],
                'premium_calculation_method' => $planData['premium_calculation_method'],
                'insurance_training_levy_percentage' => $planData['insurance_training_levy_percentage'],
                'stamp_duty_amount' => $planData['stamp_duty_amount'],
            ]);

            $this->info("✓ Created plan: {$planData['name']} ({$planData['code']})");

            // Attach service categories with benefit and base amounts
            $syncData = [];
            foreach ($planData['benefits'] as $categoryName => $amounts) {
                $serviceCategory = ServiceCategory::where('name', $categoryName)->first();

                if (!$serviceCategory) {
                    $this->warn("  ⚠ Service category '{$categoryName}' not found. Skipping...");
                    continue;
                }

                $syncData[$serviceCategory->id] = [
                    'benefit_amount' => $amounts['benefit'],
                    'base_amount' => $amounts['base'],
                    'waiting_period_days' => $categoryName === 'Maternity' ? 365 : 0,
                    'is_enabled' => true,
                ];
            }

            // Sync service categories
            $plan->serviceCategories()->sync($syncData);
            $this->info("  → Attached " . count($syncData) . " service categories");
            $createdCount++;
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Created: {$createdCount} plans");
        $this->info("  Skipped: {$skippedCount} plans (already exist)");
        $this->newLine();
        $this->info("✓ Plans creation completed for {$insuranceCompany->name}!");

        return 0;
    }
}
