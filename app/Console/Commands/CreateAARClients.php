<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\InsuranceCompany;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\ServiceCategory;
use App\Models\MedicalQuestion;
use App\Models\MedicalQuestionResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CreateAARClients extends Command
{
    protected $signature = 'clients:create-aar {--delete-existing : Delete existing clients before creating new ones}';
    protected $description = 'Create sample clients for AAR Health Insurance (Code: 61647619)';

    public function handle()
    {
        $companyCode = '61647619';
        
        // Find AAR Health Insurance
        $insuranceCompany = InsuranceCompany::where('code', $companyCode)->first();
        
        if (!$insuranceCompany) {
            $this->error("Insurance company with code {$companyCode} not found. Please create it first.");
            return 1;
        }

        $this->info("Found insurance company: {$insuranceCompany->name} (Code: {$insuranceCompany->code})");

        // Delete existing clients if requested
        if ($this->option('delete-existing')) {
            $existingClientsCount = Client::whereHas('policies', function($query) use ($insuranceCompany) {
                $query->where('insurance_company_id', $insuranceCompany->id);
            })->count();
            
            if ($existingClientsCount > 0) {
                if ($this->confirm("Delete {$existingClientsCount} existing client(s) for {$insuranceCompany->name}?")) {
                    // Delete policies first (which will cascade to policy benefits)
                    Policy::where('insurance_company_id', $insuranceCompany->id)->delete();
                    // Delete medical question responses
                    $clientIds = Client::whereHas('policies', function($query) use ($insuranceCompany) {
                        $query->where('insurance_company_id', $insuranceCompany->id);
                    })->pluck('id');
                    MedicalQuestionResponse::whereIn('client_id', $clientIds)->delete();
                    // Delete clients
                    Client::whereHas('policies', function($query) use ($insuranceCompany) {
                        $query->where('insurance_company_id', $insuranceCompany->id);
                    })->delete();
                    $this->info("✓ Deleted {$existingClientsCount} existing client(s)");
                    $this->newLine();
                }
            }
        }

        // Get an active plan (Premium plan)
        $plan = Plan::where('insurance_company_id', $insuranceCompany->id)
            ->where('name', 'Premium')
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            $this->error('Premium plan not found for this insurance company. Please create plans first.');
            return 1;
        }

        $this->info("Using plan: {$plan->name}");

        // Get service categories for benefits
        $serviceCategories = ServiceCategory::whereIn('name', ['Inpatient', 'Outpatient', 'Funeral Expenses', 'Maternity', 'Optical', 'Dental'])
            ->where('is_active', true)
            ->get()
            ->keyBy('name');

        // Get medical questions for this insurance company
        $medicalQuestions = MedicalQuestion::where('insurance_company_id', $insuranceCompany->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        // Simulate a user login for context (needed for policy number generation)
        $user = \App\Models\User::where('insurance_company_id', $insuranceCompany->id)->first();
        if ($user) {
            \Auth::loginUsingId($user->id);
        }

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
            'cell_phone' => '+256700000101',
            'email' => 'john.doe.aar@example.com',
            'home_physical_address' => 'Plot 123, Kampala Road, Kampala',
            'occupation' => 'Software Engineer',
            'employer_name' => 'Tech Solutions Ltd',
            'nationality' => 'Ugandan',
            'next_of_kin_first_name' => 'Jane',
            'next_of_kin_surname' => 'Doe',
            'next_of_kin_relation' => 'Spouse',
            'next_of_kin_cell_phone' => '+256700000102',
            'number_of_dependents' => 2,
            'selected_benefits' => ['Inpatient', 'Outpatient', 'Maternity'],
            'medical_responses' => [
                1 => ['response' => 'no'],
                2 => ['response' => 'no'],
                3 => ['response' => 'yes', 'additional_info' => 'Type 2 diabetes, well controlled with medication'],
                4 => ['response' => 'yes', 'additional_info' => 'Mild hypertension, on medication'],
                5 => ['response' => 'yes', 'additional_info' => 'Metformin, Lisinopril'],
            ],
        ], $serviceCategories, $medicalQuestions);

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
            'cell_phone' => '+256700000103',
            'email' => 'mary.smith.aar@example.com',
            'home_physical_address' => 'Plot 456, Entebbe Road, Kampala',
            'occupation' => 'Teacher',
            'employer_name' => 'Kampala Primary School',
            'nationality' => 'Ugandan',
            'next_of_kin_first_name' => 'Peter',
            'next_of_kin_surname' => 'Smith',
            'next_of_kin_relation' => 'Husband',
            'next_of_kin_cell_phone' => '+256700000104',
            'number_of_dependents' => 1,
            'selected_benefits' => ['Inpatient', 'Outpatient', 'Optical', 'Dental'],
            'medical_responses' => [
                1 => ['response' => 'no'],
                2 => ['response' => 'no'],
                3 => ['response' => 'no'],
                4 => ['response' => 'no'],
                5 => ['response' => 'no'],
                9 => ['response' => 'yes', 'additional_info' => 'Mild asthma, uses inhaler occasionally'],
            ],
        ], $serviceCategories, $medicalQuestions);

        if ($client2) {
            $this->info("✓ Client 2 created: {$client2->first_name} {$client2->surname} (ID: {$client2->id})");
            if ($client2->policies->count() > 0) {
                $policy = $client2->policies->first();
                $this->info("  Policy: {$policy->policy_number} - Total Premium: UGX " . number_format($policy->total_premium_due, 2));
            }
        }

        $this->info("\n✓ Successfully created 2 clients for {$insuranceCompany->name}!");
        return 0;
    }

    private function createClient($insuranceCompany, $plan, $clientData, $serviceCategories, $medicalQuestions)
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
        
        // Select benefits based on client data
        $selectedBenefits = [];
        $selectedBenefitNames = $clientData['selected_benefits'] ?? ['Inpatient'];
        
        foreach ($selectedBenefitNames as $benefitName) {
            $category = $serviceCategories->get($benefitName);
            if ($category) {
                $selectedBenefits[] = $category->id;
            }
        }
        
        // Inpatient is always mandatory
        if (!in_array($serviceCategories->get('Inpatient')->id, $selectedBenefits)) {
            $selectedBenefits[] = $serviceCategories->get('Inpatient')->id;
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

        // Calculate dependents premium using tiered multipliers
        $numberOfDependents = $clientData['number_of_dependents'] ?? 0;
        $dependentsPremium = $plan->calculateDependentPremium($basePremium, $numberOfDependents);

        // Subtotal
        $subtotalPremium = $basePremium + $dependentsPremium;

        // Apply medical question monetary impacts
        $premiumAdjustment = 0;
        $deductibleAdjustment = 0;
        
        if (isset($clientData['medical_responses']) && $medicalQuestions->count() > 0) {
            foreach ($clientData['medical_responses'] as $questionId => $responseArray) {
                $question = $medicalQuestions->firstWhere('id', $questionId);
                if ($question && $question->has_monetary_impact && $question->monetary_impact_type !== 'none') {
                    $response = strtolower(trim($responseArray['response'] ?? ''));
                    $appliesTo = strtolower(trim($question->monetary_impact_applies_to_response ?? 'yes'));

                    $shouldApply = ($response === $appliesTo || str_contains($response, $appliesTo));

                    if ($shouldApply && $question->monetary_impact_amount) {
                        $impactAmount = $question->monetary_impact_amount;
                        if ($question->monetary_impact_is_percentage) {
                            $impactAmount = $basePremium * ($impactAmount / 100);
                        }

                        if ($question->monetary_impact_type === 'premium_adjustment') {
                            $premiumAdjustment += $impactAmount;
                        } elseif ($question->monetary_impact_type === 'deductible_adjustment') {
                            $deductibleAdjustment += $impactAmount;
                        }
                    }
                }
            }
        }
        
        $subtotalPremium += $premiumAdjustment;

        // Training levy
        $trainingLevyPercentage = ($plan->insurance_training_levy_percentage ?? 0.50) / 100;
        $insuranceTrainingLevy = $subtotalPremium * $trainingLevyPercentage;

        // Stamp duty
        $stampDuty = $plan->stamp_duty_amount ?? 35000;

        // Total premium due
        $totalPremiumDue = $subtotalPremium + $insuranceTrainingLevy + $stampDuty;

        // Generate policy number using the controller method
        $policyNumber = $this->generatePolicyNumber($insuranceCompany);

        // Policy dates
        $inceptionDate = now();
        $expiryDate = $inceptionDate->copy()->addYear();

        // Map plan name to valid plan_type enum value
        // The enum only allows: Prestige, Executive, Standard Plus, Standard, Regular, Budget
        $planTypeMap = [
            'Premium' => 'Prestige', // Map Premium to Prestige
            'Executive' => 'Executive',
            'Standard Plus' => 'Standard Plus',
            'Standard' => 'Standard',
            'Regular' => 'Regular',
            'Budget' => 'Budget',
            'Prestige' => 'Prestige',
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
            'has_deductible' => $deductibleAdjustment > 0,
            'deductible_amount' => $deductibleAdjustment,
            'telemedicine_only' => false,
        ]);

        // Create policy benefits
        foreach ($plan->serviceCategories as $serviceCategory) {
            $pivot = $serviceCategory->pivot;
            $isSelected = in_array($serviceCategory->id, $selectedBenefits);
            $isInpatient = $serviceCategory->name === 'Inpatient';

            if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->benefit_amount) {
                PolicyBenefit::create([
                    'policy_id' => $policy->id,
                    'service_category_id' => $serviceCategory->id,
                    'benefit_amount' => $pivot->benefit_amount,
                    'base_amount' => $pivot->base_amount,
                    'used_amount' => 0,
                    'remaining_amount' => $pivot->benefit_amount,
                    'waiting_period_days' => $pivot->waiting_period_days,
                    'is_enabled' => $pivot->is_enabled,
                ]);
            }
        }

        // Save medical question responses
        if (isset($clientData['medical_responses']) && $medicalQuestions->count() > 0) {
            foreach ($clientData['medical_responses'] as $questionId => $responseArray) {
                $question = $medicalQuestions->firstWhere('id', $questionId);
                if ($question) {
                    MedicalQuestionResponse::create([
                        'client_id' => $client->id,
                        'medical_question_id' => $question->id,
                        'response' => $responseArray['response'] ?? null,
                        'additional_info' => $responseArray['additional_info'] ?? null,
                        'triggers_exclusion' => $question->triggersExclusion($responseArray['response'] ?? ''),
                    ]);
                }
            }
        }

        return $client->fresh(['policies']);
    }

    private function generatePolicyNumber($insuranceCompany): string
    {
        // Use the same logic as ClientController
        $format = $insuranceCompany->policy_number_format ?? '{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}';
        $randomLength = $insuranceCompany->policy_number_random_length ?? 6;
        $randomType = $insuranceCompany->policy_number_random_type ?? 'alphanumeric';
        $companyCodeLength = $insuranceCompany->policy_number_company_code_length ?? 3;

        $attempts = 0;
        $maxAttempts = 100;

        do {
            $policyNumber = $format;

            // Replace {COMPANY}
            $companyCode = strtoupper(substr($insuranceCompany->code ?? 'INS', 0, $companyCodeLength));
            $policyNumber = str_replace('{COMPANY}', $companyCode, $policyNumber);

            // Replace date parts
            $policyNumber = str_replace('{YEAR}', now()->format('Y'), $policyNumber);
            $policyNumber = str_replace('{YEAR2}', now()->format('y'), $policyNumber);
            $policyNumber = str_replace('{MONTH}', now()->format('m'), $policyNumber);
            $policyNumber = str_replace('{DAY}', now()->format('d'), $policyNumber);

            // Replace {RANDOM}
            $randomPart = '';
            if ($randomType === 'numeric') {
                $randomPart = Str::random($randomLength, '0123456789');
            } elseif ($randomType === 'alphabetic') {
                $randomPart = Str::random($randomLength, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            } else { // alphanumeric
                $randomPart = Str::random($randomLength, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
            }
            $policyNumber = str_replace('{RANDOM}', $randomPart, $policyNumber);

            $attempts++;
            if ($attempts > $maxAttempts) {
                throw new \Exception('Unable to generate unique policy number after multiple attempts.');
            }
        } while (Policy::where('policy_number', $policyNumber)->exists());

        return $policyNumber;
    }
}
