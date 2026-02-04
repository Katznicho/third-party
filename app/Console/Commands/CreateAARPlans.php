<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InsuranceCompany;
use App\Models\Plan;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

class CreateAARPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:create-aar {--delete-existing : Delete existing plans before creating new ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create plans for AAR Health Insurance (Code: 61647619)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyCode = '61647619';
        $companyName = 'AAR Health Insurance';

        // Find the insurance company
        $insuranceCompany = InsuranceCompany::where('code', $companyCode)->first();

        if (!$insuranceCompany) {
            $this->error("Insurance company with code '{$companyCode}' not found!");
            $this->info("Please create the company first or check the code.");
            return 1;
        }

        $this->info("Found insurance company: {$insuranceCompany->name} (ID: {$insuranceCompany->id})");
        $this->newLine();

        // Delete existing plans if requested
        if ($this->option('delete-existing')) {
            $existingPlansCount = Plan::where('insurance_company_id', $insuranceCompany->id)->count();
            if ($existingPlansCount > 0) {
                if ($this->confirm("Delete {$existingPlansCount} existing plan(s) for {$insuranceCompany->name}?")) {
                    Plan::where('insurance_company_id', $insuranceCompany->id)->delete();
                    $this->info("✓ Deleted {$existingPlansCount} existing plan(s)");
                    $this->newLine();
                }
            }
        }

        // Define plans with benefit amounts and base amounts
        $plans = [
            [
                'name' => 'Premium',
                'code' => 'AAR-PRE',
                'description' => 'Premium health insurance plan with comprehensive coverage',
                'sort_order' => 1,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 65,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 250000000, 'base' => 6000000],
                    'Outpatient' => ['benefit' => 8000000, 'base' => 250000],
                    'Maternity' => ['benefit' => 7000000, 'base' => 180000],
                    'Optical' => ['benefit' => 1500000, 'base' => 60000],
                    'Dental' => ['benefit' => 1500000, 'base' => 60000],
                    'Funeral Expenses' => ['benefit' => 4000000, 'base' => 120000],
                ],
            ],
            [
                'name' => 'Executive',
                'code' => 'AAR-EXE',
                'description' => 'Executive health insurance plan with excellent coverage',
                'sort_order' => 2,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 65,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 150000000, 'base' => 4000000],
                    'Outpatient' => ['benefit' => 6000000, 'base' => 180000],
                    'Maternity' => ['benefit' => 6000000, 'base' => 150000],
                    'Optical' => ['benefit' => 1200000, 'base' => 50000],
                    'Dental' => ['benefit' => 1200000, 'base' => 50000],
                    'Funeral Expenses' => ['benefit' => 3500000, 'base' => 100000],
                ],
            ],
            [
                'name' => 'Standard Plus',
                'code' => 'AAR-STD+',
                'description' => 'Standard Plus health insurance plan with good coverage',
                'sort_order' => 3,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 70,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 80000000, 'base' => 2500000],
                    'Outpatient' => ['benefit' => 4000000, 'base' => 120000],
                    'Maternity' => ['benefit' => 5000000, 'base' => 120000],
                    'Optical' => ['benefit' => 800000, 'base' => 35000],
                    'Dental' => ['benefit' => 800000, 'base' => 35000],
                    'Funeral Expenses' => ['benefit' => 3000000, 'base' => 80000],
                ],
            ],
            [
                'name' => 'Standard',
                'code' => 'AAR-STD',
                'description' => 'Standard health insurance plan with basic coverage',
                'sort_order' => 4,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 70,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 40000000, 'base' => 1200000],
                    'Outpatient' => ['benefit' => 3000000, 'base' => 80000],
                    'Maternity' => ['benefit' => 4000000, 'base' => 100000],
                    'Optical' => ['benefit' => 500000, 'base' => 25000],
                    'Dental' => ['benefit' => 500000, 'base' => 25000],
                    'Funeral Expenses' => ['benefit' => 2500000, 'base' => 70000],
                ],
            ],
            [
                'name' => 'Regular',
                'code' => 'AAR-REG',
                'description' => 'Regular health insurance plan with essential coverage',
                'sort_order' => 5,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 75,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 20000000, 'base' => 600000],
                    'Outpatient' => ['benefit' => 2000000, 'base' => 60000],
                    'Maternity' => ['benefit' => 3000000, 'base' => 70000],
                    'Optical' => ['benefit' => 400000, 'base' => 20000],
                    'Dental' => ['benefit' => 400000, 'base' => 20000],
                    'Funeral Expenses' => ['benefit' => 2000000, 'base' => 50000],
                ],
            ],
            [
                'name' => 'Budget',
                'code' => 'AAR-BUD',
                'description' => 'Budget-friendly health insurance plan with basic coverage',
                'sort_order' => 6,
                'min_enrollment_age' => 18,
                'max_enrollment_age' => 75,
                'dependent_coverage_multiplier' => 0.50,
                'premium_calculation_method' => 'benefit_based',
                'insurance_training_levy_percentage' => 0.50,
                'stamp_duty_amount' => 35000,
                'benefits' => [
                    'Inpatient' => ['benefit' => 8000000, 'base' => 400000],
                    'Outpatient' => ['benefit' => 1200000, 'base' => 40000],
                    'Maternity' => ['benefit' => 2000000, 'base' => 50000],
                    'Optical' => ['benefit' => 250000, 'base' => 12000],
                    'Dental' => ['benefit' => 250000, 'base' => 12000],
                    'Funeral Expenses' => ['benefit' => 1200000, 'base' => 35000],
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
