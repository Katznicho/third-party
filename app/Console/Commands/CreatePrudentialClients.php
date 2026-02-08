<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\InsuranceCompany;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\ServiceCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreatePrudentialClients extends Command
{
    protected $signature = 'app:create-prudential-clients';
    protected $description = 'Create 2 sample clients for Prudential Assurance Uganda Limited';

    public function handle()
    {
        // Find Prudential Assurance Uganda Limited
        $insuranceCompany = InsuranceCompany::where('code', '56178642')->first();
        
        if (!$insuranceCompany) {
            $this->error('Insurance company with code 56178642 not found. Please create it first.');
            return 1;
        }

        $this->info("Found insurance company: {$insuranceCompany->name} (Code: {$insuranceCompany->code})");

        // Get an active plan for this company
        $plan = Plan::where('insurance_company_id', $insuranceCompany->id)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            $this->error('No active plan found for this insurance company. Please create a plan first.');
            return 1;
        }

        $this->info("Using plan: {$plan->name}");

        // Get service categories for benefits
        $serviceCategories = ServiceCategory::whereIn('name', ['Inpatient', 'Outpatient', 'Funeral Expenses', 'Maternity', 'Optical', 'Dental'])
            ->where('is_active', true)
            ->get()
            ->keyBy('name');

        // Client 1: John Doe
        $this->info('Creating Client 1: John Doe...');
        $client1 = $this->createClient($insuranceCompany, $plan, [
            'first_name' => 'John',
            'surname' => 'Doe',
            'other_names' => 'Michael',
            'title' => 'Mr',
            'id_passport_no' => 'CF' . rand(100000, 999999),
            'gender' => 'Male',
            'date_of_birth' => '1985-05-15',
            'marital_status' => 'Married',
            'cell_phone' => '+256700000001',
            'email' => 'john.doe@example.com',
            'home_physical_address' => 'Plot 123, Kampala Road, Kampala',
            'occupation' => 'Software Engineer',
            'employer_name' => 'Tech Solutions Ltd',
            'nationality' => 'Ugandan',
            'next_of_kin_first_name' => 'Jane',
            'next_of_kin_surname' => 'Doe',
            'next_of_kin_relation' => 'Spouse',
            'next_of_kin_cell_phone' => '+256700000002',
            'number_of_dependents' => 2,
        ], $serviceCategories);

        if ($client1) {
            $this->info("✓ Client 1 created: {$client1->first_name} {$client1->surname} (ID: {$client1->id})");
            if ($client1->policies->count() > 0) {
                $policy = $client1->policies->first();
                $this->info("  Policy: {$policy->policy_number} - Total Premium: UGX " . number_format($policy->total_premium_due, 2));
            }
        }

        // Client 2: Mary Smith
        $this->info('Creating Client 2: Mary Smith...');
        $client2 = $this->createClient($insuranceCompany, $plan, [
            'first_name' => 'Mary',
            'surname' => 'Smith',
            'other_names' => 'Elizabeth',
            'title' => 'Mrs',
            'id_passport_no' => 'CF' . rand(100000, 999999),
            'gender' => 'Female',
            'date_of_birth' => '1990-08-22',
            'marital_status' => 'Married',
            'cell_phone' => '+256700000003',
            'email' => 'mary.smith@example.com',
            'home_physical_address' => 'Plot 456, Entebbe Road, Kampala',
            'occupation' => 'Teacher',
            'employer_name' => 'Kampala Primary School',
            'nationality' => 'Ugandan',
            'next_of_kin_first_name' => 'Peter',
            'next_of_kin_surname' => 'Smith',
            'next_of_kin_relation' => 'Husband',
            'next_of_kin_cell_phone' => '+256700000004',
            'number_of_dependents' => 1,
        ], $serviceCategories);

        if ($client2) {
            $this->info("✓ Client 2 created: {$client2->first_name} {$client2->surname} (ID: {$client2->id})");
            if ($client2->policies->count() > 0) {
                $policy = $client2->policies->first();
                $this->info("  Policy: {$policy->policy_number} - Total Premium: UGX " . number_format($policy->total_premium_due, 2));
            }
        }

        $this->info("\n✓ Successfully created 2 clients for Prudential Assurance Uganda Limited!");
        return 0;
    }

    private function createClient($insuranceCompany, $plan, $clientData, $serviceCategories)
    {
        // Ensure unique ID/Passport number
        $idPassportNo = $clientData['id_passport_no'];
        while (Client::where('id_passport_no', $idPassportNo)->exists()) {
            $idPassportNo = 'CF' . rand(100000, 999999);
        }
        $clientData['id_passport_no'] = $idPassportNo;

        // Create client
        $client = Client::create(array_merge($clientData, [
            'type' => 'principal',
            'plan_id' => $plan->id,
            'is_active' => true,
            'desired_start_date' => now(),
        ]));

        // Get plan categories
        $planCategories = $plan->serviceCategories->keyBy('name');
        
        // Select benefits (Inpatient is mandatory, select a few others)
        $selectedBenefits = [];
        $selectedBenefits[] = $serviceCategories->get('Inpatient')->id; // Mandatory
        if ($planCategories->has('Outpatient')) {
            $selectedBenefits[] = $serviceCategories->get('Outpatient')->id;
        }
        if ($planCategories->has('Maternity')) {
            $selectedBenefits[] = $serviceCategories->get('Maternity')->id;
        }

        // Calculate premium
        $basePremium = 0;
        foreach ($plan->serviceCategories as $serviceCategory) {
            $pivot = $serviceCategory->pivot;
            $isSelected = in_array($serviceCategory->id, $selectedBenefits);
            $isInpatient = $serviceCategory->name === 'Inpatient';
            
            if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->base_amount) {
                $basePremium += $pivot->base_amount;
            }
        }

        // Apply plan calculation method
        $calculationMethod = $plan->premium_calculation_method ?? 'benefit_based';
        if ($calculationMethod === 'fixed') {
            $basePremium = $plan->base_premium ?? 0;
        } elseif ($calculationMethod === 'hybrid') {
            $basePremium = ($plan->base_premium ?? 0) + $basePremium;
        }

        // Calculate dependents premium
        $numberOfDependents = $clientData['number_of_dependents'] ?? 0;
        // Calculate dependents premium using tiered multipliers
        $dependentsPremium = $plan->calculateDependentPremium($basePremium, $numberOfDependents);

        // Subtotal
        $subtotalPremium = $basePremium + $dependentsPremium;

        // Training levy
        $trainingLevyPercentage = ($plan->insurance_training_levy_percentage ?? 0.50) / 100;
        $insuranceTrainingLevy = $subtotalPremium * $trainingLevyPercentage;

        // Stamp duty
        $stampDuty = $plan->stamp_duty_amount ?? 35000;

        // Total premium due
        $totalPremiumDue = $subtotalPremium + $insuranceTrainingLevy + $stampDuty;

        // Generate policy number
        $policyNumber = $this->generatePolicyNumber();

        // Policy dates
        $inceptionDate = now();
        $expiryDate = $inceptionDate->copy()->addYear();

        // Plan type mapping
        $planTypeMap = [
            'Prestige' => 'Prestige',
            'Executive' => 'Executive',
            'Standard Plus' => 'Standard Plus',
            'Standard' => 'Standard',
            'Regular' => 'Regular',
            'Budget' => 'Budget',
        ];
        $planType = $planTypeMap[$plan->name] ?? 'Standard';

        // Create policy
        $policy = Policy::create([
            'policy_number' => $policyNumber,
            'insurance_company_id' => $insuranceCompany->id,
            'principal_member_id' => $client->id,
            'plan_type' => $planType,
            'inception_date' => $inceptionDate,
            'expiry_date' => $expiryDate,
            'desired_start_date' => now(),
            'total_premium' => $subtotalPremium,
            'insurance_training_levy' => $insuranceTrainingLevy,
            'stamp_duty' => $stampDuty,
            'total_premium_due' => $totalPremiumDue,
            'status' => 'active',
            'is_paid' => false,
            'has_deductible' => false,
            'telemedicine_only' => false,
        ]);

        // Create policy benefits
        foreach ($plan->serviceCategories as $serviceCategory) {
            $pivot = $serviceCategory->pivot;
            $isSelected = in_array($serviceCategory->id, $selectedBenefits);
            $isInpatient = $serviceCategory->name === 'Inpatient';

            if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->benefit_amount) {
                $benefitData = [
                    'policy_id' => $policy->id,
                    'service_category_id' => $serviceCategory->id,
                    'benefit_amount' => $pivot->benefit_amount,
                    'used_amount' => 0,
                    'remaining_amount' => $pivot->benefit_amount,
                ];

                // Add category-specific fields
                if ($serviceCategory->name === 'Funeral Expenses') {
                    $benefitData['hospital_cash_per_day'] = $pivot->benefit_amount;
                    $benefitData['hospital_cash_max_days'] = 30;
                } elseif ($serviceCategory->name === 'Life Cover') {
                    $benefitData['life_cover_amount'] = $pivot->benefit_amount;
                }

                PolicyBenefit::create($benefitData);
            }
        }

        return $client->fresh(['policies']);
    }

    private function generatePolicyNumber(): string
    {
        $year = now()->format('Y');
        $random = strtoupper(Str::random(6));
        return "{$year}-{$random}";
    }
}
