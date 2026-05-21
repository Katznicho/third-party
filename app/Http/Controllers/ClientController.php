<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\Plan;
use App\Models\InsuranceCompany;
use App\Models\PolicyDeductibleLedger;
use App\Models\InsuranceAuthorization;
use App\Models\Payment;
use App\Payments\YoAPI;
use App\Services\ClientRegistrationServiceChargeService;
use App\Support\PaymentReference;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        if (!$insuranceCompanyId) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be associated with an insurance company to view clients.');
        }

        $insuranceCompany = InsuranceCompany::find($insuranceCompanyId);

        // This insurer only: same insurance_company_id as the logged-in user, and plan belongs to this insurer
        $clientsQuery = Client::where('insurance_company_id', $insuranceCompanyId)
            ->whereNotNull('plan_id')
            ->whereHas('plan', function ($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->with(['policies' => function ($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            }, 'principalMember', 'plan']);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $term = '%' . addcslashes($search, '%_\\') . '%';
            $clientsQuery->where(function ($q) use ($term, $insuranceCompanyId) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('surname', 'like', $term)
                    ->orWhere('other_names', 'like', $term)
                    ->orWhere('id_passport_no', 'like', $term)
                    ->orWhere('cell_phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('kashtre_client_id', 'like', $term)
                    ->orWhereHas('plan', function ($pq) use ($term, $insuranceCompanyId) {
                        $pq->where('insurance_company_id', $insuranceCompanyId)
                            ->where('name', 'like', $term);
                    })
                    ->orWhereHas('policies', function ($pq) use ($term, $insuranceCompanyId) {
                        $pq->where('insurance_company_id', $insuranceCompanyId)
                            ->where('policy_number', 'like', $term);
                    });
            });
        }

        $clients = $clientsQuery->latest()->paginate(15)->withQueryString();

        return view('clients.index', compact('clients', 'insuranceCompany', 'search'));
    }

    /**
     * Build validation rules based on insurance company settings
     */
    private function buildValidationRules(InsuranceCompany $insuranceCompany, bool $isUpdate = false, $clientId = null): array
    {
        $requiredFields = $insuranceCompany->getRequiredFields();
        
        // Base rules that are always the same
        $rules = [
            'type' => 'required|in:principal,dependent',
            'principal_member_id' => 'required_if:type,dependent|nullable|exists:clients,id',
            'plan_id' => 'required_if:type,principal|nullable|exists:plans,id',
            'desired_start_date' => 'nullable|date',
            'number_of_dependents' => 'nullable|integer|min:0|max:20',
            'has_deductible' => 'nullable|boolean',
            'deductible_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'coinsurance_percentage' => 'nullable|numeric|min:0|max:100',
            'copay_max_limit' => 'nullable|numeric|min:0',
            'copay_contributes_to_deductible' => 'nullable|boolean',
            'coinsurance_contributes_to_deductible' => 'nullable|boolean',
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'insurance_payable_percentage' => 'nullable|numeric|min:0|max:100',
            'premium_grace_days' => 'nullable|integer|min:0|max:365',
            'active_period_days' => 'nullable|integer|min:0|max:3650',
        ];
        
        // Dynamic rules based on required fields
        $fieldRules = [
            'surname' => in_array('surname', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'first_name' => 'required|string|max:255', // Always required
            'other_names' => in_array('other_names', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'title' => in_array('title', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'id_passport_no' => $isUpdate 
                ? 'required|string|max:255|unique:clients,id_passport_no,' . $clientId
                : 'required|string|max:255|unique:clients,id_passport_no', // Always required
            'gender' => in_array('gender', $requiredFields) ? 'required|in:Male,Female' : 'nullable|in:Male,Female',
            'tin' => in_array('tin', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'date_of_birth' => in_array('date_of_birth', $requiredFields) ? 'required|date' : 'nullable|date',
            'marital_status' => in_array('marital_status', $requiredFields) ? 'required|in:Single,Married,Divorced,Widowed' : 'nullable|in:Single,Married,Divorced,Widowed',
            'height' => in_array('height', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'weight' => in_array('weight', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'employer_name' => in_array('employer_name', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'occupation' => in_array('occupation', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'nationality' => in_array('nationality', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'home_physical_address' => in_array('home_physical_address', $requiredFields) ? 'required|string|max:500' : 'nullable|string|max:500',
            'office_physical_address' => in_array('office_physical_address', $requiredFields) ? 'required|string|max:500' : 'nullable|string|max:500',
            'home_telephone' => in_array('home_telephone', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'office_telephone' => in_array('office_telephone', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'cell_phone' => in_array('cell_phone', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'whatsapp_line' => in_array('whatsapp_line', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'email' => in_array('email', $requiredFields) ? 'required|email|max:255' : 'nullable|email|max:255',
            'relation_to_principal' => 'nullable|string|max:255',
            'next_of_kin_surname' => in_array('next_of_kin_surname', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'next_of_kin_first_name' => in_array('next_of_kin_first_name', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'next_of_kin_other_names' => in_array('next_of_kin_other_names', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'next_of_kin_title' => in_array('next_of_kin_title', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'next_of_kin_relation' => in_array('next_of_kin_relation', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'next_of_kin_id_passport_no' => in_array('next_of_kin_id_passport_no', $requiredFields) ? 'required|string|max:255' : 'nullable|string|max:255',
            'next_of_kin_cell_phone' => in_array('next_of_kin_cell_phone', $requiredFields) ? 'required|string|max:50' : 'nullable|string|max:50',
            'next_of_kin_email' => in_array('next_of_kin_email', $requiredFields) ? 'required|email|max:255' : 'nullable|email|max:255',
            'next_of_kin_post_address' => in_array('next_of_kin_post_address', $requiredFields) ? 'required|string|max:500' : 'nullable|string|max:500',
            'next_of_kin_physical_address' => in_array('next_of_kin_physical_address', $requiredFields) ? 'required|string|max:500' : 'nullable|string|max:500',
        ];
        
        return array_merge($rules, $fieldRules);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company to create clients.');
        }

        $medicalQuestions = \App\Models\MedicalQuestion::where('insurance_company_id', $user->insurance_company_id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        
        $insuranceCompany = $user->insuranceCompany;
        $requiredFields = $insuranceCompany ? $insuranceCompany->getRequiredFields() : ['first_name', 'id_passport_no'];
        $countries = \App\Services\CountriesService::getCountriesForSelect();
        
        return view('clients.create', compact('medicalQuestions', 'requiredFields', 'insuranceCompany', 'countries'));
    }

    /**
     * Check for potential duplicate clients by first name, surname and date of birth.
     */
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
        ]);

        $client = Client::query()
            ->where('insurance_company_id', auth()->user()->insurance_company_id)
            ->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper(trim($validated['first_name']))])
            ->whereRaw('UPPER(TRIM(surname)) = ?', [strtoupper(trim($validated['surname']))])
            ->whereDate('date_of_birth', $validated['date_of_birth'])
            ->latest('id')
            ->first();

        if (!$client) {
            return response()->json([
                'duplicate' => false,
            ]);
        }

        return response()->json([
            'duplicate' => true,
            'client' => [
                'id' => $client->id,
                'type' => $client->type,
                'title' => $client->title,
                'surname' => $client->surname,
                'first_name' => $client->first_name,
                'other_names' => $client->other_names,
                'id_passport_no' => $client->id_passport_no,
                'gender' => $client->gender,
                'tin' => $client->tin,
                'date_of_birth' => optional($client->date_of_birth)->format('Y-m-d'),
                'marital_status' => $client->marital_status,
                'height' => $client->height,
                'weight' => $client->weight,
                'employer_name' => $client->employer_name,
                'occupation' => $client->occupation,
                'nationality' => $client->nationality,
                'home_physical_address' => $client->home_physical_address,
                'office_physical_address' => $client->office_physical_address,
                'home_telephone' => $client->home_telephone,
                'office_telephone' => $client->office_telephone,
                'cell_phone' => $client->cell_phone,
                'whatsapp_line' => $client->whatsapp_line,
                'email' => $client->email,
                'next_of_kin_surname' => $client->next_of_kin_surname,
                'next_of_kin_first_name' => $client->next_of_kin_first_name,
                'next_of_kin_other_names' => $client->next_of_kin_other_names,
                'next_of_kin_title' => $client->next_of_kin_title,
                'next_of_kin_relation' => $client->next_of_kin_relation,
                'next_of_kin_id_passport_no' => $client->next_of_kin_id_passport_no,
                'next_of_kin_cell_phone' => $client->next_of_kin_cell_phone,
                'next_of_kin_email' => $client->next_of_kin_email,
                'next_of_kin_post_address' => $client->next_of_kin_post_address,
                'next_of_kin_physical_address' => $client->next_of_kin_physical_address,
            ],
        ]);
    }

    /**
     * Calculate Kashtre vendor service charge for client registration premium (AJAX).
     */
    public function calculateKashtreServiceCharge(Request $request, ClientRegistrationServiceChargeService $chargeService)
    {
        $user = auth()->user();
        if (! $user->insuranceCompany) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'chargeable_base' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $chargeableBase = isset($validated['chargeable_base'])
            ? (float) $validated['chargeable_base']
            : (float) ($validated['subtotal'] ?? 0);

        $result = $chargeService->calculateForInsurer(
            $user->insuranceCompany,
            $chargeableBase
        );

        return response()->json([
            'amount' => $result['amount'],
            'chargeable_base' => $result['chargeable_base'],
            'connected_business_id' => $result['connected_business_id'],
            'has_connection' => $result['has_connection'],
            'formatted_service_charge' => $result['formatted_service_charge'],
            'schedule_source' => $result['schedule_source'],
            'tier' => $result['tier'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Client creation started', [
                'user_id' => auth()->id(),
                'request_data_keys' => array_keys($request->all())
            ]);

            $user = auth()->user();
            $insuranceCompany = $user->insuranceCompany;
            if (!$insuranceCompany) {
                Log::error('Client creation failed: No insurance company', ['user_id' => $user->id]);
                return redirect()->back()->with('error', 'You must be associated with an insurance company.')->withInput();
            }

            $validationRules = $this->buildValidationRules($insuranceCompany);
            Log::debug('Validation rules', ['rules_count' => count($validationRules)]);

            $validated = $request->validate($validationRules);
            Log::info('Validation passed', ['validated_keys' => array_keys($validated)]);

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

        // Validate premium payment method when principal with plan
        if (($validated['type'] ?? '') === 'principal' && !empty($validated['plan_id'])) {
            $allowedMethods = $insuranceCompany->payment_methods ?: array_keys(InsuranceCompany::getPaymentMethodOptions());
            $request->validate([
                'premium_payment_method' => ['required', 'string', 'in:' . implode(',', $allowedMethods)],
            ]);
            if (($request->input('premium_payment_method')) === 'mobile_money') {
                $request->validate([
                    'premium_payment_phone' => ['required', 'string', 'max:50'],
                ]);
            }
        }

        // When premium payment is Mobile Money, use payment phone as client cell_phone for the prompt and storage
        if (($request->input('premium_payment_method')) === 'mobile_money' && $request->filled('premium_payment_phone')) {
            $validated['cell_phone'] = $request->input('premium_payment_phone');
        }

        // Remove fields that don't exist in the database (services_category, payment_methods, premium_payment_method)
        unset($validated['services_category']);
        unset($validated['payment_methods']);
        unset($validated['premium_payment_method']);

        // Set insurance company ID from authenticated user
        $validated['insurance_company_id'] = auth()->user()->insurance_company_id;

        $client = Client::create($validated);
        $policyNumber = null;
        
        // Initialize monetary adjustment variables
        $premiumAdjustment = 0;
        $deductibleAdjustment = 0;

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
                
                // Add base amount (what client pays) to premium if selected/enabled and has an amount
                if (($isSelected || $isInpatient) && $pivot->is_enabled && $pivot->base_amount) {
                    $basePremium += $pivot->base_amount;
                }
            }
            
            // Calculate premium based on plan's calculation method
            $calculationMethod = $plan->premium_calculation_method ?? 'benefit_based';
            
            if ($calculationMethod === 'fixed') {
                // Use base premium only
                $basePremium = $plan->base_premium ?? 0;
            } elseif ($calculationMethod === 'hybrid') {
                // Base premium + benefit amounts
                $basePremium = ($plan->base_premium ?? 0) + $basePremium;
            }
            // else 'benefit_based' - already calculated above
            
            // Calculate dependents premium using tiered multipliers
            $dependentsPremium = $plan->calculateDependentPremium($basePremium, $numberOfDependents);
            
            // Subtotal premium (principal + dependents)
            $subtotalPremium = $basePremium + $dependentsPremium;
            
            // Calculate insurance training levy using plan's percentage
            $trainingLevyPercentage = ($plan->insurance_training_levy_percentage ?? 0.50) / 100;
            $insuranceTrainingLevy = $subtotalPremium * $trainingLevyPercentage;
            
            // Stamp duty from plan settings
            $stampDuty = $plan->stamp_duty_amount ?? 35000;
            
            // Calculate monetary impact from medical questions
            $coverageLimitAdjustments = [];
            
            if ($request->has('medical_questions')) {
                foreach ($request->medical_questions as $questionId => $responseData) {
                    $question = \App\Models\MedicalQuestion::find($questionId);
                    if (!$question || !$question->has_monetary_impact || $question->monetary_impact_type === 'none') {
                        continue;
                    }
                    
                    $response = strtolower(trim($responseData['response'] ?? ''));
                    $appliesTo = strtolower(trim($question->monetary_impact_applies_to_response ?? 'yes'));
                    
                    // Check if response matches the trigger
                    $shouldApply = false;
                    if ($question->question_type === 'yes_no') {
                        $shouldApply = ($response === $appliesTo);
                    } else {
                        // For text/date/number, check if response matches or contains the trigger
                        $shouldApply = ($response === $appliesTo || str_contains($response, $appliesTo));
                    }
                    
                    if ($shouldApply && $question->monetary_impact_amount) {
                        $impactAmount = $question->monetary_impact_amount;
                        
                        if ($question->monetary_impact_type === 'premium_adjustment') {
                            if ($question->monetary_impact_is_percentage) {
                                // Percentage of base premium
                                $premiumAdjustment += ($basePremium * $impactAmount / 100);
                            } else {
                                // Fixed amount
                                $premiumAdjustment += $impactAmount;
                            }
                        } elseif ($question->monetary_impact_type === 'deductible_adjustment') {
                            if ($question->monetary_impact_is_percentage) {
                                // Percentage adjustment (would need base deductible, but we'll use fixed for now)
                                $deductibleAdjustment += $impactAmount;
                            } else {
                                // Fixed amount
                                $deductibleAdjustment += $impactAmount;
                            }
                        } elseif ($question->monetary_impact_type === 'coverage_limit_adjustment') {
                            // Store for later use (could adjust annual/lifetime limits)
                            $coverageLimitAdjustments[] = [
                                'amount' => $impactAmount,
                                'is_percentage' => $question->monetary_impact_is_percentage,
                            ];
                        }
                    }
                }
            }
            
            // Apply premium adjustment
            $subtotalPremium += $premiumAdjustment;
            
            // Recalculate training levy after premium adjustment
            $insuranceTrainingLevy = $subtotalPremium * $trainingLevyPercentage;

            $chargeableBase = $subtotalPremium + $insuranceTrainingLevy + $stampDuty;
            $kashtreChargeResult = app(ClientRegistrationServiceChargeService::class)
                ->calculateForInsurer($insuranceCompany, $chargeableBase);
            $kashtreServiceCharge = $kashtreChargeResult['amount'];

            // Total premium due = billable premium + Kashtre service charge (pushed to client)
            $totalPremiumDue = $chargeableBase + $kashtreServiceCharge;
            
            // Apply deductible adjustment if applicable
            $finalDeductibleAmount = null;
            if (($validated['has_deductible'] ?? false) && isset($validated['deductible_amount'])) {
                $finalDeductibleAmount = $validated['deductible_amount'] + $deductibleAdjustment;
            } elseif ($deductibleAdjustment > 0) {
                // If no deductible was set but adjustment exists, set it
                $finalDeductibleAmount = $deductibleAdjustment;
            }
            
            // Get insurance company defaults for contribution flags
            $insuranceCompany = auth()->user()->insuranceCompany;
            $defaultCopayContributes = $insuranceCompany ? $insuranceCompany->copay_contributes_to_deductible : false;
            $defaultCoinsuranceContributes = $insuranceCompany ? $insuranceCompany->coinsurance_contributes_to_deductible : false;
            
            // Use provided values or fall back to company defaults
            $copayContributes = isset($validated['copay_contributes_to_deductible']) 
                ? (bool)$validated['copay_contributes_to_deductible'] 
                : $defaultCopayContributes;
            $coinsuranceContributes = isset($validated['coinsurance_contributes_to_deductible']) 
                ? (bool)$validated['coinsurance_contributes_to_deductible'] 
                : $defaultCoinsuranceContributes;
            
            // Create policy with calculated premium - status inactive until premium is paid (Yo/cash, confirmed by cron for Yo)
            $policy = Policy::create([
                'policy_number' => $policyNumber,
                'insurance_company_id' => auth()->user()->insurance_company_id,
                'principal_member_id' => $client->id,
                'plan_type' => $planType,
                'inception_date' => $inceptionDate,
                'expiry_date' => $expiryDate,
                'desired_start_date' => $desiredStartDate,
                'total_premium' => $subtotalPremium, // Includes principal + dependents + medical question adjustments
                'insurance_training_levy' => $insuranceTrainingLevy,
                'stamp_duty' => $stampDuty,
                'kashtre_service_charge' => $kashtreServiceCharge,
                'kashtre_connected_business_id' => $kashtreChargeResult['connected_business_id'],
                'total_premium_due' => $totalPremiumDue,
                'status' => 'inactive',
                'is_paid' => false,
                'has_deductible' => $validated['has_deductible'] ?? false,
                'copay_amount' => $validated['copay_amount'] ?? null,
                'coinsurance_percentage' => $validated['coinsurance_percentage'] ?? null,
                'deductible_amount' => $finalDeductibleAmount,
                'copay_max_limit' => $validated['copay_max_limit'] ?? null,
                'copay_contributes_to_deductible' => $copayContributes,
                'coinsurance_contributes_to_deductible' => $coinsuranceContributes,
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
                        'coverage_percent' => \App\Models\ConnectedCompanyItemCoverage::normalizePercent(
                            (float) ($pivot->coverage_percent ?? 100)
                        ),
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
                    '. Policy %s has been created%s. Total Premium Due: UGX %s (Premium: UGX %s, Training Levy: UGX %s, Stamp Duty: UGX %s%s)',
                    $policyNumber,
                    $dependentsText,
                    number_format($policy->total_premium_due, 2),
                    number_format($policy->total_premium, 2),
                    number_format($policy->insurance_training_levy, 2),
                    number_format($policy->stamp_duty, 2),
                    ($policy->kashtre_service_charge ?? 0) > 0
                        ? ', Kashtre service charge: UGX ' . number_format($policy->kashtre_service_charge, 2)
                        : ''
                );
                
                // Add monetary adjustment information
                if ($premiumAdjustment != 0) {
                    $adjustmentType = $premiumAdjustment > 0 ? 'increased' : 'decreased';
                    $successMessage .= sprintf(
                        '. Premium %s by UGX %s due to medical questionnaire responses',
                        $adjustmentType,
                        number_format(abs($premiumAdjustment), 2)
                    );
                }
                
                if ($deductibleAdjustment != 0) {
                    $adjustmentType = $deductibleAdjustment > 0 ? 'increased' : 'decreased';
                    $successMessage .= sprintf(
                        '. Deductible %s by UGX %s due to medical questionnaire responses',
                        $adjustmentType,
                        number_format(abs($deductibleAdjustment), 2)
                    );
                }
            } else {
                $successMessage .= '. Policy ' . $policyNumber . ' has been created.';
            }
        }
        
        if ($hasExclusions) {
            $successMessage .= ' WARNING: This client has responses that trigger exclusion list criteria.';
        }

        // Create client account
        try {
            $accountNumber = \App\Models\ClientAccount::generateAccountNumber($insuranceCompany);
            
            \App\Models\ClientAccount::create([
                'client_id' => $client->id,
                'insurance_company_id' => $insuranceCompany->id,
                'account_number' => $accountNumber,
                'account_type' => $validated['type'] === 'principal' ? 'individual' : 'individual',
                'status' => 'active',
                'opening_balance' => 0,
                'current_balance' => 0,
                'total_debits' => 0,
                'total_credits' => 0,
                'available_balance' => 0,
                'opened_date' => now(),
                'auto_generate_statements' => true,
                'statement_frequency' => 'monthly',
            ]);
            
            Log::info('Client account created', [
                'client_id' => $client->id,
                'account_number' => $accountNumber
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create client account', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
        }

        Log::info('Client created successfully', [
            'client_id' => $client->id,
            'policy_number' => $policyNumber,
            'has_exclusions' => $hasExclusions
        ]);

        // If principal with policy created, handle premium payment per selected method
        $premiumPaymentMethod = $request->input('premium_payment_method');
        if ($validated['plan_id'] && $validated['type'] === 'principal' && $policyNumber && $premiumPaymentMethod) {
            try {
                $policy = isset($policy) ? $policy : Policy::where('policy_number', $policyNumber)->first();
                if (!$policy) {
                    $successMessage .= ' Premium payment was not created (policy not found).';
                } else {
                    $paymentReference = PaymentReference::forPremium($policy->id);
                    $amount = (float) $policy->total_premium_due;
                    $paymentMethodForDb = in_array($premiumPaymentMethod, ['p_card', 'v_card']) ? 'card' : $premiumPaymentMethod;

                    if ($premiumPaymentMethod === 'mobile_money') {
                        $paymentPhone = $client->cell_phone;
                        if ($paymentPhone) {
                            $phone = preg_replace('/\s+/', '', $paymentPhone);
                            if (str_starts_with($phone, '+')) {
                                $phone = substr($phone, 1);
                            } elseif (str_starts_with($phone, '0')) {
                                $phone = '256' . substr($phone, 1);
                            }

                            if (strlen($phone) >= 9) {
                                if (app()->environment('local')) {
                                    $policy->update([
                                        'status' => 'active',
                                        'is_paid' => true,
                                        'payment_date' => now(),
                                    ]);
                                    $payment = Payment::create([
                                        'payment_reference' => $paymentReference,
                                        'invoice_id' => null,
                                        'policy_id' => $policy->id,
                                        'client_id' => $client->id,
                                        'payment_type' => 'premium_payment',
                                        'amount' => $amount,
                                        'paid_amount' => $amount,
                                        'balance_amount' => 0,
                                        'payment_method' => 'mobile_money',
                                        'mobile_money_number' => $phone,
                                        'transaction_id' => 'LOCAL-TEST-' . uniqid(),
                                        'status' => 'completed',
                                        'payment_date' => now(),
                                        'processed_at' => now(),
                                        'payment_notes' => 'Premium payment (mobile money) auto-completed in local environment',
                                        'processed_by' => auth()->id(),
                                    ]);
                                    \App\Services\PaymentCompletionService::ensureTransactionAndAccountForCompletedPayment($payment);
                                    $successMessage .= ' Premium paid automatically in local environment. Policy is now active.';
                                } else {
                                    $yoApi = new YoAPI(
                                        config('payments.yo_username'),
                                        config('payments.yo_password')
                                    );
                                    $yoApi->set_instant_notification_url(config('payments.webhook_url'));
                                    $yoApi->set_external_reference($paymentReference);
                                    $narrative = 'Premium payment - Policy ' . $policy->policy_number . ' - ' . $client->full_name;
                                    if (strlen($narrative) > 160) {
                                        $narrative = substr($narrative, 0, 157) . '...';
                                    }
                                    Log::info('Initiating Yo premium payment from client creation', [
                                        'policy_id' => $policy->id,
                                        'client_id' => $client->id,
                                        'phone' => $phone,
                                        'amount' => $amount,
                                        'reference' => $paymentReference,
                                    ]);
                                    $yoResult = $yoApi->ac_deposit_funds($phone, $amount, $narrative);
                                    Log::info('YoAPI premium payment response (client creation)', ['result' => $yoResult]);

                                    if (isset($yoResult['Status']) && $yoResult['Status'] === 'OK' && !empty($yoResult['TransactionReference'])) {
                                        $transactionRef = $yoResult['TransactionReference'];
                                        Payment::create([
                                            'payment_reference' => $paymentReference,
                                            'invoice_id' => null,
                                            'policy_id' => $policy->id,
                                            'client_id' => $client->id,
                                            'payment_type' => 'premium_payment',
                                            'amount' => $amount,
                                            'paid_amount' => $amount,
                                            'balance_amount' => 0,
                                            'payment_method' => 'mobile_money',
                                            'mobile_money_number' => $phone,
                                            'transaction_id' => $transactionRef,
                                            'status' => 'pending',
                                            'payment_date' => now(),
                                            'processed_at' => null,
                                            'payment_notes' => 'Premium payment (mobile money) initiated on client creation',
                                            'payment_metadata' => [
                                                'yo_transaction_reference' => $transactionRef,
                                                'yo_status' => $yoResult['Status'] ?? null,
                                                'policy_id' => $policy->id,
                                                'insurance_company_id' => $insuranceCompany->id,
                                            ],
                                            'processed_by' => auth()->id(),
                                        ]);
                                        $successMessage .= ' Mobile money request sent to ' . $phone . '. Policy will become active once payment is confirmed.';
                                    } else {
                                        $errorMessage = $yoResult['StatusMessage'] ?? $yoResult['ErrorMessage'] ?? 'Unknown error';
                                        $successMessage .= ' However, mobile money premium payment could not be initiated: ' . $errorMessage;
                                    }
                                }
                            } else {
                                $successMessage .= ' Payment phone number is invalid, premium payment was not initiated.';
                            }
                        } else {
                            $successMessage .= ' No payment phone provided for mobile money. Premium payment was not initiated.';
                        }
                    } else {
                        // Non–mobile money: create pending payment; staff updates manually within grace period
                        // Use client-specific grace if set, otherwise company + method grace
                        $clientGraceDays = $client->premium_grace_days;
                        $methodGraceDays = $insuranceCompany->getGracePeriodForMethod($premiumPaymentMethod);
                        $graceDays = is_null($clientGraceDays) ? $methodGraceDays : max(0, min(365, (int) $clientGraceDays));
                        $dueAt = now()->addDays($graceDays);

                        Payment::create([
                            'payment_reference' => $paymentReference,
                            'invoice_id' => null,
                            'policy_id' => $policy->id,
                            'client_id' => $client->id,
                            'payment_type' => 'premium_payment',
                            'amount' => $amount,
                            'paid_amount' => 0,
                            'balance_amount' => $amount,
                            'payment_method' => $paymentMethodForDb,
                            'status' => 'pending',
                            'payment_date' => now(),
                            'processed_at' => null,
                            'payment_notes' => 'Premium payment (' . $premiumPaymentMethod . ') – to be updated manually. Due by ' . $dueAt->toDateString() . ' (grace: ' . $graceDays . ' days).',
                            'payment_metadata' => [
                                'premium_payment_method_selected' => $premiumPaymentMethod,
                                'grace_days' => $graceDays,
                                'due_at' => $dueAt->toDateString(),
                                'policy_id' => $policy->id,
                                'insurance_company_id' => $insuranceCompany->id,
                            ],
                        ]);
                        // Mark policy as pending_payment until premium is received
                        $policy->update(['status' => 'pending_payment']);

                        $successMessage .= ' Premium payment recorded as pending (' . $premiumPaymentMethod . '). Policy status is now pending payment. Update payment manually within ' . $graceDays . ' day(s) (due by ' . $dueAt->toDateString() . ').';
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error during premium payment setup (client creation)', [
                    'client_id' => $client->id,
                    'policy_number' => $policyNumber,
                    'error' => $e->getMessage(),
                ]);
                $successMessage .= ' Premium payment could not be set up. Please try again from the client page.';
            }
        }

        return redirect()->route('clients.show', $client)
            ->with('success', $successMessage)
            ->with('has_exclusions', $hasExclusions);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Client creation validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['password', '_token'])
            ]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Client creation failed with exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['password', '_token'])
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while creating the client: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate a unique policy number based on insurance company settings
     */
    private function generatePolicyNumber(): string
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        // Get settings with defaults
        $format = $insuranceCompany->policy_number_format ?? '{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}';
        $randomLength = $insuranceCompany->policy_number_random_length ?? 6;
        $randomType = $insuranceCompany->policy_number_random_type ?? 'alphanumeric';
        $companyCodeLength = $insuranceCompany->policy_number_company_code_length ?? 3;
        
        // Generate company code prefix
        $companyCode = strtoupper(substr($insuranceCompany->code ?? 'INS', 0, $companyCodeLength));
        
        // Generate random part based on type
        $randomPart = $this->generateRandomPart($randomLength, $randomType);
        
        // Replace placeholders in format
        $policyNumber = $format;
        $policyNumber = str_replace('{COMPANY}', $companyCode, $policyNumber);
        $policyNumber = str_replace('{YEAR}', now()->format('Y'), $policyNumber);
        $policyNumber = str_replace('{MONTH}', now()->format('m'), $policyNumber);
        $policyNumber = str_replace('{DAY}', now()->format('d'), $policyNumber);
        $policyNumber = str_replace('{YEAR2}', now()->format('y'), $policyNumber); // 2-digit year
        $policyNumber = str_replace('{RANDOM}', $randomPart, $policyNumber);
        
        // Ensure uniqueness
        $attempts = 0;
        $maxAttempts = 100;
        $originalPolicyNumber = $policyNumber;
        
        while (Policy::where('policy_number', $policyNumber)->exists()) {
            $randomPart = $this->generateRandomPart($randomLength, $randomType);
            $policyNumber = str_replace('{RANDOM}', $randomPart, $originalPolicyNumber);
            $policyNumber = str_replace('{COMPANY}', $companyCode, $policyNumber);
            $policyNumber = str_replace('{YEAR}', now()->format('Y'), $policyNumber);
            $policyNumber = str_replace('{MONTH}', now()->format('m'), $policyNumber);
            $policyNumber = str_replace('{DAY}', now()->format('d'), $policyNumber);
            $policyNumber = str_replace('{YEAR2}', now()->format('y'), $policyNumber);
            
            $attempts++;
            if ($attempts > $maxAttempts) {
                throw new \Exception('Unable to generate unique policy number after multiple attempts.');
            }
        }
        
        return $policyNumber;
    }
    
    /**
     * Generate random part based on type
     */
    private function generateRandomPart(int $length, string $type): string
    {
        switch ($type) {
            case 'numeric':
                $characters = '0123456789';
                break;
            case 'alphabetic':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alphanumeric':
            default:
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                break;
        }
        
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $random;
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Check if client belongs to this insurance company
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        
        // Also check if client is a dependent of a principal with a policy
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        
        // Also allow viewing of open enrollment clients synced from kashtre (even without policies)
        $isOpenEnrollmentClient = $client->registered_via_open_enrollment && 
                                 $client->insurance_company_id == $insuranceCompanyId;
        
        if (!$hasPolicy && !$isOpenEnrollmentClient) {
            abort(403, 'You do not have access to this client.');
        }
        
        $client->load([
            'principalMember', 
            'dependents', 
            'policies' => function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            },
            'policies.insuranceCompany',
            'policies.benefits.serviceCategory',
            'medicalQuestionResponses.question',
            'plan'
        ]);
        
        // Check if client has any pending mobile money payments (for manual status check)
        $hasPendingMobileMoneyPayments = Payment::where('client_id', $client->id)
            ->where('status', 'pending')
            ->where('payment_method', 'mobile_money')
            ->whereNotNull('transaction_id')
            ->exists();

        // Premium payments for this client (to show grace period, payment method, and mark as received)
        $premiumPayments = Payment::where('client_id', $client->id)
            ->where('payment_type', 'premium_payment')
            ->with('policy')
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('clients.show', compact('client', 'hasPendingMobileMoneyPayments', 'premiumPayments'));
    }

    /**
     * Manually check YoAPI status for this client's pending mobile money payments.
     * Useful when the cron job fails or is delayed.
     */
    public function checkMobileMoneyPayments(Client $client)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;

        // Reuse the same access guard as show()
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        if (!$hasPolicy) {
            abort(403, 'You do not have access to this client.');
        }

        // Find this client's pending mobile money payments
        $pendingPayments = Payment::where('client_id', $client->id)
            ->where('status', 'pending')
            ->where('payment_method', 'mobile_money')
            ->whereNotNull('transaction_id')
            ->whereNotNull('payment_metadata')
            ->get();

        if ($pendingPayments->isEmpty()) {
            return redirect()->route('clients.show', $client)
                ->with('info', 'No pending mobile money payments found for this client.');
        }

        $yoPayments = new YoAPI(
            config('payments.yo_username'),
            config('payments.yo_password')
        );

        $processedCount = 0;
        $completedCount = 0;
        $failedCount = 0;

        foreach ($pendingPayments as $payment) {
            try {
                $transactionReference = $payment->transaction_id;
                if (!$transactionReference) {
                    continue;
                }

                $statusCheck = $yoPayments->ac_transaction_check_status($transactionReference);

                if (!isset($statusCheck['TransactionStatus'])) {
                    continue;
                }

                \Illuminate\Support\Facades\DB::beginTransaction();

                try {
                    if ($statusCheck['TransactionStatus'] === 'SUCCEEDED') {
                        $payment->update([
                            'status' => 'completed',
                            'paid_amount' => $payment->amount,
                            'balance_amount' => 0,
                            'cleared_date' => now(),
                            'processed_at' => now(),
                            'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                                'yo_transaction_completion_date' => $statusCheck['TransactionCompletionDate'] ?? null,
                                'yo_issued_receipt_number' => $statusCheck['IssuedReceiptNumber'] ?? null,
                                'completed_at' => now()->toDateTimeString(),
                            ]),
                        ]);
                        \App\Services\PaymentCompletionService::ensureTransactionAndAccountForCompletedPayment($payment->fresh());
                        $completedCount++;
                    } elseif ($statusCheck['TransactionStatus'] === 'FAILED') {
                        $payment->update([
                            'status' => 'failed',
                            'failure_reason' => $statusCheck['StatusMessage'] ?? $statusCheck['ErrorMessage'] ?? 'Payment failed via Yo Payments',
                            'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                                'yo_error_message' => $statusCheck['ErrorMessage'] ?? null,
                                'failed_at' => now()->toDateTimeString(),
                            ]),
                        ]);
                        $failedCount++;
                    } else {
                        // PENDING or other status: just update metadata timestamp
                        $payment->update([
                            'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                'last_status_check' => now()->toDateTimeString(),
                                'yo_status' => $statusCheck['TransactionStatus'] ?? null,
                                'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                            ]),
                        ]);
                    }

                    \Illuminate\Support\Facades\DB::commit();
                    $processedCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                }
            } catch (\Exception $e) {
                // Ignore individual failures, continue with others
            }
        }

        $message = "Checked {$processedCount} mobile money payment(s) for this client.";
        if ($completedCount > 0) {
            $message .= " Completed: {$completedCount}.";
        }
        if ($failedCount > 0) {
            $message .= " Failed: {$failedCount}.";
        }

        return redirect()->route('clients.show', $client)
            ->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;
        
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company to edit clients.');
        }

        // Check if client belongs to this insurance company
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        
        // Also check if client is a dependent of a principal with a policy
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        
        if (!$hasPolicy) {
            abort(403, 'You do not have access to this client.');
        }

        // Only show principals from this insurance company
        $principals = Client::where('type', 'principal')
            ->where('id', '!=', $client->id)
            ->whereHas('policies', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->get();

        $medicalQuestions = \App\Models\MedicalQuestion::where('insurance_company_id', $insuranceCompanyId)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        
        // Load existing responses and policy (filtered by insurance company)
        $client->load([
            'medicalQuestionResponses', 
            'policies' => function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            }
        ]);
        
        $insuranceCompany = $user->insuranceCompany;
        $requiredFields = $insuranceCompany ? $insuranceCompany->getRequiredFields() : ['first_name', 'id_passport_no'];
        $countries = \App\Services\CountriesService::getCountriesForSelect();
        
        return view('clients.edit', compact('client', 'principals', 'medicalQuestions', 'requiredFields', 'insuranceCompany', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;
        $insuranceCompany = $user->insuranceCompany;
        
        if (!$insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        // Check if client belongs to this insurance company
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        
        // Also check if client is a dependent of a principal with a policy
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        
        if (!$hasPolicy) {
            abort(403, 'You do not have access to this client.');
        }

        $validated = $request->validate($this->buildValidationRules($insuranceCompany, true, $client->id));

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

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
            
            // Get insurance company defaults for contribution flags
            $insuranceCompany = auth()->user()->insuranceCompany;
            $defaultCopayContributes = $insuranceCompany ? $insuranceCompany->copay_contributes_to_deductible : false;
            $defaultCoinsuranceContributes = $insuranceCompany ? $insuranceCompany->coinsurance_contributes_to_deductible : false;
            
            // Use provided values or fall back to company defaults (or keep existing policy value)
            $copayContributes = isset($validated['copay_contributes_to_deductible']) 
                ? (bool)$validated['copay_contributes_to_deductible'] 
                : ($policy->copay_contributes_to_deductible ?? $defaultCopayContributes);
            $coinsuranceContributes = isset($validated['coinsurance_contributes_to_deductible']) 
                ? (bool)$validated['coinsurance_contributes_to_deductible'] 
                : ($policy->coinsurance_contributes_to_deductible ?? $defaultCoinsuranceContributes);
            
            $policy->update([
                'has_deductible' => $validated['has_deductible'] ?? false,
                'copay_amount' => $validated['copay_amount'] ?? null,
                'coinsurance_percentage' => $validated['coinsurance_percentage'] ?? null,
                'deductible_amount' => ($validated['has_deductible'] ?? false) ? ($validated['deductible_amount'] ?? null) : null,
                'copay_max_limit' => $validated['copay_max_limit'] ?? null,
                'copay_contributes_to_deductible' => $copayContributes,
                'coinsurance_contributes_to_deductible' => $coinsuranceContributes,
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
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Check if client belongs to this insurance company
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        
        // Also check if client is a dependent of a principal with a policy
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        
        if (!$hasPolicy) {
            abort(403, 'You do not have access to this client.');
        }

        // Check if client has policies (only for this insurance company)
        if ($client->policies()->where('insurance_company_id', $insuranceCompanyId)->count() > 0) {
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

    /**
     * Show client account statement
     */
    public function accountStatement(Client $client, \App\Services\KashtreApiService $kashtreApi)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;

        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        // Check if client belongs to this insurance company (same logic as show method)
        $hasPolicy = $client->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->exists();
        
        // Also check if client is a dependent of a principal with a policy
        if (!$hasPolicy && $client->principalMember) {
            $hasPolicy = $client->principalMember->policies()
                ->where('insurance_company_id', $insuranceCompanyId)
                ->exists();
        }
        
        // Also allow viewing of open enrollment clients synced from kashtre (even without policies)
        $isOpenEnrollmentClient = $client->registered_via_open_enrollment && 
                                 $client->insurance_company_id == $insuranceCompanyId;
        
        if (!$hasPolicy && !$isOpenEnrollmentClient) {
            return redirect()->route('clients.index')->with('error', 'Client not found or you do not have access to this client.');
        }

        // Credits from Kashtre client-portion are stored on the principal member's ClientAccount / transactions.
        $client->loadMissing('principalMember', 'dependents');
        $balanceClient = $client->accountBalanceClient();
        $activityClientIds = $client->accountActivityClientIds();

        // Get or create client account (always the wallet owner — principal when applicable)
        $account = $balanceClient->account;
        if (!$account) {
            $accountNumber = \App\Models\ClientAccount::generateAccountNumber($user->insuranceCompany);

            $account = \App\Models\ClientAccount::create([
                'client_id' => $balanceClient->id,
                'insurance_company_id' => $user->insurance_company_id,
                'account_number' => $accountNumber,
                'account_type' => 'individual',
                'status' => 'active',
                'opening_balance' => 0,
                'current_balance' => 0,
                'total_debits' => 0,
                'total_credits' => 0,
                'available_balance' => 0,
                'opened_date' => $balanceClient->created_at ?? now(),
                'auto_generate_statements' => true,
                'statement_frequency' => 'monthly',
            ]);

            Log::info('Client account created on-demand', [
                'client_id' => $balanceClient->id,
                'account_number' => $accountNumber,
            ]);
        }

        // Transactions for this member + principal/dependents (same household activity)
        $transactions = \App\Models\Transaction::whereIn('client_id', $activityClientIds)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->with(['policy', 'invoice', 'payment', 'serviceCategory'])
            ->paginate(50);

        // Sum ALL rows using debit_amount / credit_amount (includes type=copayment from Kashtre client portion)
        $transactionTotals = \App\Models\Transaction::whereIn('client_id', $activityClientIds)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as sum_debits, COALESCE(SUM(credit_amount), 0) as sum_credits')
            ->first();
        $totalDebits = (float) ($transactionTotals->sum_debits ?? 0);
        $totalCredits = (float) ($transactionTotals->sum_credits ?? 0);

        $invoices = \App\Models\Invoice::whereIn('client_id', $activityClientIds)
            ->orderBy('invoice_date', 'desc')
            ->with(['policy', 'payments'])
            ->get();

        $payments = \App\Models\Payment::whereIn('client_id', $activityClientIds)
            ->orderBy('payment_date', 'desc')
            ->with(['invoice', 'policy'])
            ->get();

        // Calculate account summary
        $totalInvoices = $invoices->sum('total_amount');
        $totalPaid = $payments->sum('paid_amount');
        $totalBalance = $invoices->sum('balance_amount');

        // Authorizations attach to policy (owned by principal)
        $policyOwner = $client->principalMember ?? $client;
        $policyIds = $policyOwner->policies()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->pluck('id')
            ->all();
        $totalGuaranteed = 0;
        $totalDeductibleUsed = 0;
        $totalCopayUsed = 0;
        $totalCoinsuranceUsed = 0;

        if (!empty($policyIds)) {
            $authorizations = \App\Models\InsuranceAuthorization::where('insurance_company_id', $insuranceCompanyId)
                ->whereIn('policy_id', $policyIds)
                ->get();

            foreach ($authorizations as $auth) {
                $breakdown = $auth->breakdown ?? [];
                $totalGuaranteed += (float) ($auth->insurance_total ?? 0);
                $totalDeductibleUsed += (float) ($breakdown['deductible'] ?? 0);
                $totalCopayUsed += (float) ($breakdown['copay'] ?? 0);
                $totalCoinsuranceUsed += (float) ($breakdown['coinsurance'] ?? 0);
            }
        }

        // Update account balances
        $lastTxn = \App\Models\Transaction::whereIn('client_id', $activityClientIds)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $account->update([
            'current_balance' => $totalCredits - $totalDebits,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'available_balance' => $totalCredits - $totalDebits,
            'last_transaction_date' => $lastTxn?->transaction_date ?? $account->opened_date,
        ]);

        $accountStatementUsesPrimaryWallet = $client->id !== $balanceClient->id;

        return view('clients.account-statement', compact(
            'client',
            'account',
            'balanceClient',
            'accountStatementUsesPrimaryWallet',
            'transactions',
            'invoices',
            'payments',
            'totalInvoices',
            'totalPaid',
            'totalBalance',
            'totalDebits',
            'totalCredits',
            'totalGuaranteed',
            'totalDeductibleUsed',
            'totalCopayUsed',
            'totalCoinsuranceUsed',
        ));
    }

    /**
     * Show detailed guarantee (insurance portion) usage for a client.
     */
    public function guaranteeUsage(Client $client)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;

        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        // Ensure client belongs to this insurer
        if (!$client->policies()->where('insurance_company_id', $insuranceCompanyId)->exists()) {
            return redirect()->route('clients.index')->with('error', 'Client not found or you do not have access to this client.');
        }

        $policyIds = $client->policies()->where('insurance_company_id', $insuranceCompanyId)->pluck('id')->all();
        $authorizations = collect();
        $totalGuaranteed = 0;

        if (!empty($policyIds)) {
            $query = \App\Models\InsuranceAuthorization::where('insurance_company_id', $insuranceCompanyId)
                ->whereIn('policy_id', $policyIds);

            if ($search = request('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('external_invoice_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $authorizations = $query->orderByDesc('requested_at')->paginate(50)->withQueryString();
            $totalGuaranteed = $authorizations->sum('insurance_total');
        }

        if (request('export') === 'pdf') {
            $pdf = Pdf::loadView('clients.usage-guarantees', [
                'client' => $client,
                'authorizations' => $authorizations,
                'totalGuaranteed' => $totalGuaranteed,
                'isPdfExport' => true,
            ])->setPaper('a4', 'portrait');

            $fileName = 'authorized-guarantees-' . Str::slug($client->full_name ?? 'client') . '-' . now()->format('YmdHis') . '.pdf';

            return $pdf->download($fileName);
        }

        return view('clients.usage-guarantees', [
            'client' => $client,
            'authorizations' => $authorizations,
            'totalGuaranteed' => $totalGuaranteed,
        ]);
    }

    /**
     * Show detailed deductible usage for a client.
     */
    public function deductibleUsage(Client $client)
    {
        return $this->usageByMetric($client, 'deductible', 'clients.usage-deductible');
    }

    /**
     * Show detailed co-pay usage for a client.
     */
    public function copayUsage(Client $client)
    {
        return $this->usageByMetric($client, 'copay', 'clients.usage-copay');
    }

    /**
     * Show detailed coinsurance usage for a client.
     */
    public function coinsuranceUsage(Client $client)
    {
        return $this->usageByMetric($client, 'coinsurance', 'clients.usage-coinsurance');
    }

    /**
     * Helper to render usage pages for deductible / copay / coinsurance.
     */
    protected function usageByMetric(Client $client, string $metric, string $view)
    {
        $user = auth()->user();
        $insuranceCompanyId = $user->insurance_company_id;

        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        // Ensure client belongs to this insurer
        if (!$client->policies()->where('insurance_company_id', $insuranceCompanyId)->exists()) {
            return redirect()->route('clients.index')->with('error', 'Client not found or you do not have access to this client.');
        }

        $policyIds = $client->policies()->where('insurance_company_id', $insuranceCompanyId)->pluck('id')->all();
        $authorizations = collect();
        $totalMetric = 0;

        $ledgerStatement = collect();
        $policiesWithDeductible = collect();
        if ($metric === 'deductible' && !empty($policyIds)) {
            $ledgerStatement = PolicyDeductibleLedger::with(['policy:id,policy_number'])
                ->where('insurance_company_id', $insuranceCompanyId)
                ->whereIn('policy_id', $policyIds)
                ->orderBy('created_at', 'asc')
                ->get();

            $policiesWithDeductible = Policy::whereIn('id', $policyIds)
                ->where('has_deductible', true)
                ->orderBy('policy_number')
                ->get(['id', 'policy_number', 'deductible_amount']);
        }

        if (!empty($policyIds)) {
            $allAuthsQuery = \App\Models\InsuranceAuthorization::where('insurance_company_id', $insuranceCompanyId)
                ->whereIn('policy_id', $policyIds);

            if ($search = request('search')) {
                $allAuthsQuery->where(function ($q) use ($search) {
                    $q->where('external_invoice_number', 'like', "%{$search}%");
                });
            }

            $allAuths = $allAuthsQuery->orderByDesc('requested_at')->get();

            $filtered = $allAuths->filter(function (\App\Models\InsuranceAuthorization $auth) use ($metric) {
                $breakdown = $auth->breakdown ?? [];
                return isset($breakdown[$metric]) && (float) $breakdown[$metric] > 0;
            });

            $totalMetric = $filtered->sum(function ($auth) use ($metric) {
                $breakdown = $auth->breakdown ?? [];
                return (float) ($breakdown[$metric] ?? 0);
            });

            // Paginate manually
            $perPage = 50;
            $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage() ?: 1;
            $items = $filtered->forPage($page, $perPage)->values();

            $authorizations = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $viewData = [
            'client' => $client,
            'authorizations' => $authorizations,
            'totalMetric' => $totalMetric,
        ];
        if ($metric === 'deductible') {
            $viewData['ledgerStatement'] = $ledgerStatement;
            $viewData['policiesWithDeductible'] = $policiesWithDeductible;
        }

        if (request('export') === 'pdf') {
            $pdf = Pdf::loadView($view, array_merge($viewData, [
                'isPdfExport' => true,
            ]))->setPaper('a4', 'portrait');

            $fileName = $metric . '-usage-' . Str::slug($client->full_name ?? 'client') . '-' . now()->format('YmdHis') . '.pdf';

            return $pdf->download($fileName);
        }

        return view($view, $viewData);
    }

    /**
     * Show deductible ledger movements for this specific client (per-insured view).
     * Company-level ledger remains available via PolicyDeductibleLedgerController.
     */
    public function deductibleLedger(Client $client)
    {
        $user = auth()->user();
        $insuranceCompany = $user->insuranceCompany;

        if (!$insuranceCompany) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be associated with an insurance company.');
        }

        // Ensure this client has policies with the current insurer
        $policyIds = $client->policies()
            ->where('insurance_company_id', $insuranceCompany->id)
            ->pluck('id')
            ->all();

        if (empty($policyIds)) {
            return redirect()->route('clients.account-statement', $client)
                ->with('error', 'This client has no policies with your company.');
        }

        // Visit-level breakdown lives on insurance_authorizations (created at invoice authorization).
        // policy_deductible_ledgers rows are appended only after Kashtre confirms client portion payment.
        $authQuery = InsuranceAuthorization::with(['policy'])
            ->where('insurance_company_id', $insuranceCompany->id)
            ->whereIn('policy_id', $policyIds)
            ->orderByDesc('requested_at');

        if ($policyNumber = request('policy_number')) {
            $authQuery->whereHas('policy', function ($q) use ($policyNumber) {
                $q->where('policy_number', 'like', '%' . $policyNumber . '%');
            });
        }

        if ($invoiceNumber = request('invoice_number')) {
            $authQuery->where(function ($q) use ($invoiceNumber) {
                $q->where('external_invoice_number', 'like', '%' . $invoiceNumber . '%')
                    ->orWhere('kashtre_invoice_id', 'like', '%' . $invoiceNumber . '%');
            });
        }

        $authorizations = $authQuery->paginate(20)->withQueryString();

        $ledgerByAuthId = PolicyDeductibleLedger::whereIn(
            'authorization_id',
            $authorizations->getCollection()->pluck('id')->filter()->values()->all()
        )->get()->keyBy('authorization_id');

        return view('clients.deductible-ledger', [
            'client' => $client,
            'authorizations' => $authorizations,
            'ledgerByAuthId' => $ledgerByAuthId,
        ]);
    }

}
