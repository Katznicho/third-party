<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Get all clients (both with and without policies for this insurance company)
        // For now, show all clients, but filter policies by insurance company
        $clients = Client::with(['policies' => function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            }, 'principalMember', 'plan'])
            ->latest()
            ->paginate(15);
            
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $medicalQuestions = \App\Models\MedicalQuestion::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        
        return view('clients.create', compact('medicalQuestions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:principal,dependent',
            'principal_member_id' => 'required_if:type,dependent|nullable|exists:clients,id',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:50',
            'id_passport_no' => 'required|string|max:255|unique:clients,id_passport_no',
            'gender' => 'nullable|in:Male,Female',
            'tin' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'employer_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'home_physical_address' => 'nullable|string|max:500',
            'office_physical_address' => 'nullable|string|max:500',
            'home_telephone' => 'nullable|string|max:50',
            'office_telephone' => 'nullable|string|max:50',
            'cell_phone' => 'nullable|string|max:50',
            'whatsapp_line' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'relation_to_principal' => 'nullable|string|max:255',
            'next_of_kin_surname' => 'nullable|string|max:255',
            'next_of_kin_first_name' => 'nullable|string|max:255',
            'next_of_kin_other_names' => 'nullable|string|max:255',
            'next_of_kin_title' => 'nullable|string|max:50',
            'next_of_kin_relation' => 'nullable|string|max:255',
            'next_of_kin_id_passport_no' => 'nullable|string|max:255',
            'next_of_kin_cell_phone' => 'nullable|string|max:50',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_post_address' => 'nullable|string|max:500',
            'next_of_kin_physical_address' => 'nullable|string|max:500',
            'has_deductible' => 'nullable|boolean',
            'deductible_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'coinsurance_percentage' => 'nullable|numeric|min:0|max:100',
            'copay_max_limit' => 'nullable|numeric|min:0',
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'plan_id' => 'required_if:type,principal|nullable|exists:plans,id', // Plan required for principal members
            'desired_start_date' => 'nullable|date',
            'number_of_dependents' => 'nullable|integer|min:0|max:20',
        ]);

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

        $client = Client::create($validated);
        $policyNumber = null;

        // If plan is selected and client is principal, create policy and policy benefits
        if ($validated['plan_id'] && $validated['type'] === 'principal') {
            $plan = Plan::with('serviceCategories')->findOrFail($validated['plan_id']);
            
            // Generate unique policy number
            $policyNumber = $this->generatePolicyNumber();
            
            // Set policy dates
            $desiredStartDate = $validated['desired_start_date'] ? \Carbon\Carbon::parse($validated['desired_start_date']) : now();
            $inceptionDate = $desiredStartDate;
            $expiryDate = $inceptionDate->copy()->addYear();
            
            // Map plan name to plan_type enum value (must match enum values)
            $planTypeMap = [
                'Prestige' => 'Prestige',
                'Executive' => 'Executive',
                'Standard Plus' => 'Standard Plus',
                'Standard' => 'Standard',
                'Regular' => 'Regular',
                'Budget' => 'Budget',
            ];
            $planType = $planTypeMap[$plan->name] ?? 'Standard'; // Default to 'Standard' if not found
            
            // Get selected benefits from request
            $selectedBenefits = $request->input('selected_benefits.' . $validated['plan_id'], []);
            
            // Get number of dependents
            $numberOfDependents = $validated['number_of_dependents'] ?? 0;
            
            // Calculate premium based on selected benefits BEFORE creating policy
            $basePremium = 0;
            
            // Calculate premium from selected service categories
            foreach ($plan->serviceCategories as $serviceCategory) {
                $pivot = $serviceCategory->pivot;
                
                // Check if this benefit was selected by the user
                $isSelected = isset($selectedBenefits[$serviceCategory->id]);
                
                // Inpatient is mandatory, so always include it if it exists
                $isInpatient = $serviceCategory->name === 'Inpatient';
                
                // Add benefit amount to premium if selected/enabled and has an amount
                if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->benefit_amount) {
                    $basePremium += $pivot->benefit_amount;
                }
            }
            
            // Calculate dependents premium (50% of base premium per dependent)
            $dependentMultiplier = 0.5; // Can be configured per plan if needed
            $dependentsPremium = $basePremium * $dependentMultiplier * $numberOfDependents;
            
            // Subtotal premium (principal + dependents)
            $subtotalPremium = $basePremium + $dependentsPremium;
            
            // Calculate insurance training levy (0.5% of subtotal premium)
            $insuranceTrainingLevy = $subtotalPremium * 0.005;
            
            // Stamp duty (default 35,000 UGX)
            $stampDuty = 35000;
            
            // Total premium due = subtotal premium + levy + stamp duty
            $totalPremiumDue = $subtotalPremium + $insuranceTrainingLevy + $stampDuty;
            
            // Create policy with calculated premium
            $policy = Policy::create([
                'policy_number' => $policyNumber,
                'insurance_company_id' => auth()->user()->insurance_company_id,
                'principal_member_id' => $client->id,
                'plan_type' => $planType,
                'inception_date' => $inceptionDate,
                'expiry_date' => $expiryDate,
                'desired_start_date' => $desiredStartDate,
                'total_premium' => $subtotalPremium, // Includes principal + dependents
                'insurance_training_levy' => $insuranceTrainingLevy,
                'stamp_duty' => $stampDuty,
                'total_premium_due' => $totalPremiumDue,
                'status' => 'active',
                'is_paid' => false,
                'has_deductible' => $validated['has_deductible'] ?? false,
                'copay_amount' => $validated['copay_amount'] ?? null,
                'coinsurance_percentage' => $validated['coinsurance_percentage'] ?? null,
                'deductible_amount' => ($validated['has_deductible'] ?? false) ? ($validated['deductible_amount'] ?? null) : null,
                'copay_max_limit' => $validated['copay_max_limit'] ?? null,
                'telemedicine_only' => $validated['telemedicine_only'] ?? false,
            ]);
            
            // Create policy benefits only for selected service categories
            foreach ($plan->serviceCategories as $serviceCategory) {
                $pivot = $serviceCategory->pivot;
                
                // Check if this benefit was selected by the user
                $isSelected = isset($selectedBenefits[$serviceCategory->id]);
                
                // Inpatient is mandatory, so always create it if it exists
                $isInpatient = $serviceCategory->name === 'Inpatient';
                
                // Only create benefit if it's selected (or mandatory like Inpatient), enabled, and has an amount
                if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->benefit_amount) {
                    $benefitData = [
                        'policy_id' => $policy->id,
                        'service_category_id' => $serviceCategory->id,
                        'benefit_amount' => $pivot->benefit_amount,
                        'used_amount' => 0,
                        'remaining_amount' => $pivot->benefit_amount,
                        'copay_percentage' => $pivot->copay_percentage ?? 0,
                        'deductible_amount' => 0, // Not used anymore, but keep for compatibility
                        'is_enabled' => true,
                        'effective_date' => $inceptionDate,
                        'expiry_date' => $expiryDate,
                    ];
                    
                    // Only set hospital cash fields if it's Hospital Cash
                    if ($serviceCategory->name === 'Hospital Cash') {
                        $benefitData['hospital_cash_per_day'] = $pivot->benefit_amount;
                        $benefitData['hospital_cash_max_days'] = 30;
                    } elseif ($serviceCategory->name === 'Life Cover') {
                        $benefitData['life_cover_amount'] = $pivot->benefit_amount;
                    }
                    
                    PolicyBenefit::create($benefitData);
                }
            }
        }

        // Save medical question responses
        if ($request->has('medical_questions')) {
            foreach ($request->medical_questions as $questionId => $responseData) {
                $question = \App\Models\MedicalQuestion::find($questionId);
                if (!$question) {
                    continue;
                }

                $response = $responseData['response'] ?? null;
                $additionalInfo = $responseData['additional_info'] ?? null;

                // Handle medication table data if present
                if ($question->additional_info_type === 'table' && $request->has("medications.{$questionId}")) {
                    $medications = $request->input("medications.{$questionId}", []);
                    // Filter out empty rows
                    $medications = array_filter($medications, function($med) {
                        return !empty($med['applicant_name']) || !empty($med['medication']) || !empty($med['diagnosis']);
                    });
                    $additionalInfo = !empty($medications) ? json_encode(array_values($medications)) : null;
                } elseif (is_string($additionalInfo)) {
                    // Try to decode if it's already JSON, otherwise store as is
                    $decoded = json_decode($additionalInfo, true);
                    $additionalInfo = $decoded !== null ? $decoded : $additionalInfo;
                }

                // Check if response triggers exclusion
                $triggersExclusion = $question->triggersExclusion($response ?? '');

                \App\Models\MedicalQuestionResponse::create([
                    'client_id' => $client->id,
                    'medical_question_id' => $questionId,
                    'response' => $response,
                    'additional_info' => is_array($additionalInfo) ? $additionalInfo : ($additionalInfo ? json_decode($additionalInfo, true) : null),
                    'triggers_exclusion' => $triggersExclusion,
                ]);
            }
        }

        // Check if client has exclusions and add warning
        $hasExclusions = $client->hasExclusions();
        
        // Build success message with policy details
        $successMessage = 'Client created successfully';
        
        if ($validated['plan_id'] && $validated['type'] === 'principal' && $policyNumber) {
            $policy = Policy::where('policy_number', $policyNumber)->first();
            if ($policy) {
                $numberOfDependents = $validated['number_of_dependents'] ?? 0;
                $dependentsText = $numberOfDependents > 0 ? " (including {$numberOfDependents} " . ($numberOfDependents == 1 ? 'dependent' : 'dependents') . ")" : '';
                $successMessage .= sprintf(
                    '. Policy %s has been created%s. Total Premium Due: UGX %s (Premium: UGX %s, Training Levy: UGX %s, Stamp Duty: UGX %s)',
                    $policyNumber,
                    $dependentsText,
                    number_format($policy->total_premium_due, 2),
                    number_format($policy->total_premium, 2),
                    number_format($policy->insurance_training_levy, 2),
                    number_format($policy->stamp_duty, 2)
                );
            } else {
                $successMessage .= '. Policy ' . $policyNumber . ' has been created.';
            }
        }
        
        if ($hasExclusions) {
            $successMessage .= ' WARNING: This client has responses that trigger exclusion list criteria.';
        }

        return redirect()->route('clients.index')
            ->with('success', $successMessage)
            ->with('has_exclusions', $hasExclusions);
    }

    /**
     * Generate a unique policy number
     */
    private function generatePolicyNumber(): string
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        $companyCode = strtoupper(substr($insuranceCompany->code ?? 'INS', 0, 3));
        
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            // Format: COMPANY-YYYYMMDD-XXXXXX (e.g., AAR-20260123-ABC123)
            $datePart = now()->format('Ymd');
            $randomPart = strtoupper(Str::random(6));
            $policyNumber = "{$companyCode}-{$datePart}-{$randomPart}";
            
            $attempts++;
            if ($attempts > $maxAttempts) {
                throw new \Exception('Unable to generate unique policy number after multiple attempts.');
            }
        } while (Policy::where('policy_number', $policyNumber)->exists());
        
        return $policyNumber;
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $client->load([
            'principalMember', 
            'dependents', 
            'policies.insuranceCompany',
            'policies.benefits.serviceCategory',
            'medicalQuestionResponses.question',
            'plan'
        ]);
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $principals = Client::where('type', 'principal')
            ->where('id', '!=', $client->id)
            ->get();
        
        $medicalQuestions = \App\Models\MedicalQuestion::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        
        // Load existing responses and policy
        $client->load(['medicalQuestionResponses', 'policies']);
        
        return view('clients.edit', compact('client', 'principals', 'medicalQuestions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'type' => 'required|in:principal,dependent',
            'principal_member_id' => 'required_if:type,dependent|nullable|exists:clients,id',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:50',
            'id_passport_no' => 'required|string|max:255|unique:clients,id_passport_no,' . $client->id,
            'gender' => 'nullable|in:Male,Female',
            'tin' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'employer_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'home_physical_address' => 'nullable|string|max:500',
            'office_physical_address' => 'nullable|string|max:500',
            'home_telephone' => 'nullable|string|max:50',
            'office_telephone' => 'nullable|string|max:50',
            'cell_phone' => 'nullable|string|max:50',
            'whatsapp_line' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'relation_to_principal' => 'nullable|string|max:255',
            'next_of_kin_surname' => 'nullable|string|max:255',
            'next_of_kin_first_name' => 'nullable|string|max:255',
            'next_of_kin_other_names' => 'nullable|string|max:255',
            'next_of_kin_title' => 'nullable|string|max:50',
            'next_of_kin_relation' => 'nullable|string|max:255',
            'next_of_kin_id_passport_no' => 'nullable|string|max:255',
            'next_of_kin_cell_phone' => 'nullable|string|max:50',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_post_address' => 'nullable|string|max:500',
            'next_of_kin_physical_address' => 'nullable|string|max:500',
            'has_deductible' => 'nullable|boolean',
            'deductible_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'coinsurance_percentage' => 'nullable|numeric|min:0|max:100',
            'copay_max_limit' => 'nullable|numeric|min:0',
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

        $client->update($validated);

        // Update policy if client is principal and has a policy
        if ($validated['type'] === 'principal' && $client->policies()->exists()) {
            $policy = $client->policies()->first();
            $policy->update([
                'has_deductible' => $validated['has_deductible'] ?? false,
                'copay_amount' => $validated['copay_amount'] ?? null,
                'coinsurance_percentage' => $validated['coinsurance_percentage'] ?? null,
                'deductible_amount' => ($validated['has_deductible'] ?? false) ? ($validated['deductible_amount'] ?? null) : null,
                'copay_max_limit' => $validated['copay_max_limit'] ?? null,
                'telemedicine_only' => $validated['telemedicine_only'] ?? false,
            ]);
        }

        // Update medical question responses
        if ($request->has('medical_questions')) {
            foreach ($request->medical_questions as $questionId => $responseData) {
                $question = \App\Models\MedicalQuestion::find($questionId);
                if (!$question) {
                    continue;
                }

                $response = $responseData['response'] ?? null;
                $additionalInfo = $responseData['additional_info'] ?? null;

                // Handle medication table data if present
                if ($question->additional_info_type === 'table' && $request->has("medications.{$questionId}")) {
                    $medications = $request->input("medications.{$questionId}", []);
                    // Filter out empty rows
                    $medications = array_filter($medications, function($med) {
                        return !empty($med['applicant_name']) || !empty($med['medication']) || !empty($med['diagnosis']);
                    });
                    $additionalInfo = !empty($medications) ? json_encode(array_values($medications)) : null;
                } elseif (is_string($additionalInfo)) {
                    // Try to decode if it's already JSON, otherwise store as is
                    $decoded = json_decode($additionalInfo, true);
                    $additionalInfo = $decoded !== null ? $decoded : $additionalInfo;
                }

                // Check if response triggers exclusion
                $triggersExclusion = $question->triggersExclusion($response ?? '');

                // Update or create response
                \App\Models\MedicalQuestionResponse::updateOrCreate(
                    [
                        'client_id' => $client->id,
                        'medical_question_id' => $questionId,
                    ],
                    [
                        'response' => $response,
                        'additional_info' => is_array($additionalInfo) ? $additionalInfo : ($additionalInfo ? json_decode($additionalInfo, true) : null),
                        'triggers_exclusion' => $triggersExclusion,
                    ]
                );
            }
        }

        // Check if client has exclusions
        $hasExclusions = $client->fresh()->hasExclusions();
        $successMessage = 'Client updated successfully.';
        
        if ($hasExclusions) {
            $successMessage .= ' WARNING: This client has responses that trigger exclusion list criteria.';
        }

        return redirect()->route('clients.index')
            ->with('success', $successMessage)
            ->with('has_exclusions', $hasExclusions);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        // Check if client has policies
        if ($client->policies()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete client with existing policies. Please remove policies first.');
        }

        // Check if client has dependents
        if ($client->dependents()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete principal member with dependents. Please remove dependents first.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
