<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InsuranceCompany;
use App\Models\MedicalQuestion;

class CreatePrudentialMedicalQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medical-questions:create-prudential';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create 10 medical questions for Prudential Assurance Uganda Limited (Code: 56178642)';

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

        // Define 10 comprehensive medical questions
        $questions = [
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with or treated for HIV/AIDS?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['HIV', 'AIDS', 'positive'],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please provide details',
                'order' => 1,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 15,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 15% for HIV/AIDS diagnosis',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with or treated for cancer, tumor, or any malignant growth?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['cancer', 'tumor', 'malignant', 'carcinoma'],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please specify type and treatment details',
                'order' => 2,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 20,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 20% for cancer diagnosis',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with diabetes (Type 1 or Type 2)?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Type of diabetes and current management',
                'order' => 3,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 10,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 10% for diabetes',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with hypertension (high blood pressure) or heart disease?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please specify condition and medications',
                'order' => 4,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 8,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 8% for hypertension/heart disease',
            ],
            [
                'question_text' => 'Are you or any of your dependents currently taking any prescribed medications?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'table',
                'additional_info_label' => 'Medication Details',
                'order' => 5,
                'has_monetary_impact' => false,
                'monetary_impact_type' => 'none',
                'monetary_impact_amount' => null,
                'monetary_impact_is_percentage' => false,
                'monetary_impact_applies_to_response' => null,
                'monetary_impact_description' => null,
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with kidney disease, kidney failure, or required dialysis?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['kidney failure', 'dialysis', 'renal failure'],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please provide details',
                'order' => 6,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 25,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 25% for kidney disease',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with mental health conditions (depression, anxiety, bipolar, schizophrenia, etc.)?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please specify condition',
                'order' => 7,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 12,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 12% for mental health conditions',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever had a stroke, heart attack, or any cardiovascular event?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['stroke', 'heart attack', 'myocardial infarction'],
                'requires_additional_info' => true,
                'additional_info_type' => 'date',
                'additional_info_label' => 'Date of occurrence',
                'order' => 8,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 18,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 18% for cardiovascular events',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with asthma, chronic obstructive pulmonary disease (COPD), or any chronic respiratory condition?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please specify condition and severity',
                'order' => 9,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 7,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 7% for chronic respiratory conditions',
            ],
            [
                'question_text' => 'Have you or any of your dependents ever been diagnosed with hepatitis B or C, or any liver disease?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['hepatitis B', 'hepatitis C', 'liver cirrhosis', 'liver failure'],
                'requires_additional_info' => true,
                'additional_info_type' => 'text',
                'additional_info_label' => 'Please provide details',
                'order' => 10,
                'has_monetary_impact' => true,
                'monetary_impact_type' => 'premium_adjustment',
                'monetary_impact_amount' => 15,
                'monetary_impact_is_percentage' => true,
                'monetary_impact_applies_to_response' => 'yes',
                'monetary_impact_description' => 'Premium increased by 15% for hepatitis/liver disease',
            ],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($questions as $questionData) {
            // Check if question already exists (by text and insurance company)
            $existingQuestion = MedicalQuestion::where('insurance_company_id', $insuranceCompany->id)
                ->where('question_text', $questionData['question_text'])
                ->first();

            if ($existingQuestion) {
                $this->warn("Question already exists: " . substr($questionData['question_text'], 0, 50) . "...");
                $skippedCount++;
                continue;
            }

            // Create the question
            $question = MedicalQuestion::create([
                'insurance_company_id' => $insuranceCompany->id,
                'question_text' => $questionData['question_text'],
                'question_type' => $questionData['question_type'],
                'has_exclusion_list' => $questionData['has_exclusion_list'],
                'exclusion_keywords' => $questionData['exclusion_keywords'],
                'requires_additional_info' => $questionData['requires_additional_info'],
                'additional_info_type' => $questionData['additional_info_type'],
                'additional_info_label' => $questionData['additional_info_label'],
                'order' => $questionData['order'],
                'is_active' => true,
                'has_monetary_impact' => $questionData['has_monetary_impact'],
                'monetary_impact_type' => $questionData['monetary_impact_type'],
                'monetary_impact_amount' => $questionData['monetary_impact_amount'],
                'monetary_impact_is_percentage' => $questionData['monetary_impact_is_percentage'],
                'monetary_impact_applies_to_response' => $questionData['monetary_impact_applies_to_response'],
                'monetary_impact_description' => $questionData['monetary_impact_description'],
            ]);

            $this->info("✓ Created question {$questionData['order']}: " . substr($questionData['question_text'], 0, 60) . "...");
            if ($questionData['has_monetary_impact']) {
                $impactType = $questionData['monetary_impact_type'] === 'premium_adjustment' ? 'Premium' : 
                             ($questionData['monetary_impact_type'] === 'deductible_adjustment' ? 'Deductible' : 'Coverage');
                $amount = $questionData['monetary_impact_is_percentage'] 
                    ? $questionData['monetary_impact_amount'] . '%' 
                    : 'UGX ' . number_format($questionData['monetary_impact_amount'], 2);
                $this->info("  → Monetary Impact: {$impactType} adjustment of {$amount} for '{$questionData['monetary_impact_applies_to_response']}' response");
            }
            $createdCount++;
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Created: {$createdCount} questions");
        $this->info("  Skipped: {$skippedCount} questions (already exist)");
        $this->newLine();
        $this->info("✓ Medical questions creation completed for {$insuranceCompany->name}!");

        return 0;
    }
}
