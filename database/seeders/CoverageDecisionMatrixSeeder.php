<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoverageDecisionMatrix;
use App\Models\InsuranceCompany;
use App\Models\ServiceCategory;

class CoverageDecisionMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Whysemedical insurance company
        $insuranceCompany = InsuranceCompany::where('code', 'BPXX4Q9')->first();
        
        if (!$insuranceCompany) {
            $this->command->warn('Insurance company with code BPXX4Q9 (Whysemedical) not found. Skipping rule seeding.');
            return;
        }

        // Find Outpatient service category
        $outpatientCategory = ServiceCategory::where('code', 'OUT')->orWhere('name', 'Outpatient')->first();
        
        $rules = [
            [
                'rule_name' => 'Reject OPD Services',
                'description' => 'Automatically reject all Outpatient Department (OPD) services as they are not covered under this policy.',
                'condition_type' => 'service_category_not_covered',
                'condition_config' => [
                    'service_category_ids' => $outpatientCategory ? [$outpatientCategory->id] : [],
                ],
                'action' => 'auto_reject',
                'rejection_message' => 'OPD (Outpatient Department) services are not covered under your policy. Please contact your insurance provider for more information.',
                'review_notes_template' => 'Policy {policy_number} - OPD service rejected. Service category not covered.',
                'priority' => 10,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Flag High-Cost Services',
                'description' => 'Flag services exceeding UGX 500,000 for manual review to ensure appropriate coverage.',
                'condition_type' => 'cost_threshold_exceeded',
                'condition_config' => [
                    'cost_threshold' => 500000,
                ],
                'action' => 'flag_for_review',
                'rejection_message' => 'High-cost service requires manual review before approval.',
                'review_notes_template' => 'Policy {policy_number} - Service {service_category} for amount {amount} exceeds UGX 500,000. Requires manual review.',
                'priority' => 20,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Flag Cosmetic Procedures',
                'description' => 'Flag cosmetic and aesthetic procedures for review as they may not be covered.',
                'condition_type' => 'keyword_match',
                'condition_config' => [
                    'keywords' => ['cosmetic', 'plastic surgery', 'aesthetic', 'beauty', 'facelift', 'liposuction'],
                ],
                'action' => 'flag_for_review',
                'rejection_message' => 'Cosmetic procedure requires review. Please ensure medical necessity.',
                'review_notes_template' => 'Policy {policy_number} - Cosmetic procedure detected: {service_category} for amount {amount}. Requires medical necessity review.',
                'priority' => 30,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Require Pre-Auth for Coverage Limit Near Exhaustion',
                'description' => 'Require pre-authorization when coverage limit is 90% or more used.',
                'condition_type' => 'service_category_coverage_limit_exceeded',
                'condition_config' => [
                    'threshold_percentage' => 90,
                ],
                'action' => 'require_pre_authorization',
                'rejection_message' => 'Coverage limit is nearly exhausted. Pre-authorization is required.',
                'review_notes_template' => 'Policy {policy_number} - Service {service_category} coverage limit is {threshold_percentage}% used. Pre-authorization required.',
                'priority' => 15,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Flag Emergency Services',
                'description' => 'Flag emergency services for review to ensure proper documentation.',
                'condition_type' => 'keyword_match',
                'condition_config' => [
                    'keywords' => ['emergency', 'urgent', 'critical', 'trauma', 'accident'],
                ],
                'action' => 'flag_for_review',
                'rejection_message' => 'Emergency service requires review for proper documentation.',
                'review_notes_template' => 'Policy {policy_number} - Emergency service: {service_category} for amount {amount}. Review documentation.',
                'priority' => 25,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $ruleData) {
            $existing = CoverageDecisionMatrix::where('insurance_company_id', $insuranceCompany->id)
                ->where('rule_name', $ruleData['rule_name'])
                ->first();

            if (!$existing) {
                $ruleData['insurance_company_id'] = $insuranceCompany->id;
                CoverageDecisionMatrix::create($ruleData);
                $this->command->info("Created rule: {$ruleData['rule_name']}");
            } else {
                $this->command->warn("Rule '{$ruleData['rule_name']}' already exists. Skipping...");
            }
        }

        $this->command->info('Coverage Decision Matrix seeding completed for Whysemedical!');
    }
}
