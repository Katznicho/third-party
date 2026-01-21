<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use Illuminate\Http\Request;

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
        return view('clients.create');
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
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'plan_id' => 'required|exists:plans,id',
            'desired_start_date' => 'nullable|date',
            'agent_broker_name' => 'nullable|string|max:255',
            'medical_history' => 'nullable|array',
            'medications' => 'nullable|array',
            'pregnancy_expected_date' => 'nullable|date',
            'dependants' => 'nullable|array',
            'dependants.*.surname' => 'nullable|string|max:255',
            'dependants.*.first_name' => 'nullable|string|max:255',
            'dependants.*.other_names' => 'nullable|string|max:255',
            'dependants.*.title' => 'nullable|string|max:50',
            'dependants.*.id_passport_no' => 'nullable|string|max:255',
            'dependants.*.gender' => 'nullable|in:Male,Female',
            'dependants.*.date_of_birth' => 'nullable|date',
            'dependants.*.relation_to_principal' => 'nullable|string|max:255',
            'dependants.*.marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'dependants.*.occupation' => 'nullable|string|max:255',
            'dependants.*.height' => 'nullable|string|max:50',
            'dependants.*.weight' => 'nullable|string|max:50',
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

        // Store medical history and medications
        $validated['medical_history'] = $request->input('medical_history', []);
        $validated['regular_medications'] = $request->input('medications', []);

        // Create the principal client
        $client = Client::create($validated);

        // Create dependants if provided
        if ($request->has('dependants') && is_array($request->dependants)) {
            foreach ($request->dependants as $dependantData) {
                // Skip empty dependant entries
                if (empty($dependantData['first_name']) && empty($dependantData['surname'])) {
                    continue;
                }

                Client::create([
                    'type' => 'dependent',
                    'principal_member_id' => $client->id,
                    'surname' => $dependantData['surname'] ?? null,
                    'first_name' => $dependantData['first_name'] ?? null,
                    'other_names' => $dependantData['other_names'] ?? null,
                    'title' => $dependantData['title'] ?? null,
                    'id_passport_no' => $dependantData['id_passport_no'] ?? 'DEP-' . $client->id . '-' . uniqid(),
                    'gender' => $dependantData['gender'] ?? null,
                    'date_of_birth' => $dependantData['date_of_birth'] ?? null,
                    'relation_to_principal' => $dependantData['relation_to_principal'] ?? null,
                    'marital_status' => $dependantData['marital_status'] ?? null,
                    'occupation' => $dependantData['occupation'] ?? null,
                    'height' => $dependantData['height'] ?? null,
                    'weight' => $dependantData['weight'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        // Create Policy if plan is selected
        if ($validated['plan_id']) {
            $plan = \App\Models\Plan::with('serviceCategories')->findOrFail($validated['plan_id']);
            $insuranceCompanyId = auth()->user()->insurance_company_id;

            // Generate policy number
            $policyNumber = 'POL-' . strtoupper(substr($plan->code, 0, 3)) . '-' . date('Y') . '-' . str_pad(Policy::where('insurance_company_id', $insuranceCompanyId)->count() + 1, 6, '0', STR_PAD_LEFT);

            // Calculate dates
            $desiredStartDate = $validated['desired_start_date'] ? \Carbon\Carbon::parse($validated['desired_start_date']) : now();
            $inceptionDate = $desiredStartDate;
            $expiryDate = $inceptionDate->copy()->addYear();

            // Create Policy
            $policy = Policy::create([
                'policy_number' => $policyNumber,
                'insurance_company_id' => $insuranceCompanyId,
                'principal_member_id' => $client->id,
                'plan_type' => $plan->name,
                'inception_date' => $inceptionDate,
                'expiry_date' => $expiryDate,
                'desired_start_date' => $desiredStartDate,
                'total_premium' => 0, // Will be calculated later
                'insurance_training_levy' => 0, // 0.5% - will be calculated
                'stamp_duty' => 35000,
                'total_premium_due' => 0, // Will be calculated later
                'agent_broker_name' => $validated['agent_broker_name'] ?? null,
                'status' => 'active',
                'is_paid' => false,
                'has_deductible' => $validated['has_deductible'] ?? false,
                'telemedicine_only' => $validated['telemedicine_only'] ?? false,
            ]);

            // Create PolicyBenefits based on the selected plan
            foreach ($plan->serviceCategories as $serviceCategory) {
                $pivot = $serviceCategory->pivot;
                if ($pivot->is_enabled && $pivot->benefit_amount > 0) {
                    PolicyBenefit::create([
                        'policy_id' => $policy->id,
                        'service_category_id' => $serviceCategory->id,
                        'benefit_amount' => $pivot->benefit_amount,
                        'used_amount' => 0,
                        'remaining_amount' => $pivot->benefit_amount,
                        'hospital_cash_per_day' => $serviceCategory->name === 'Hospital Cash' ? $pivot->benefit_amount : null,
                        'hospital_cash_max_days' => $serviceCategory->name === 'Hospital Cash' ? 30 : null,
                        'life_cover_amount' => $serviceCategory->name === 'Life Cover' ? $pivot->benefit_amount : null,
                        'copay_percentage' => $pivot->copay_percentage ?? 0,
                        'deductible_amount' => $pivot->deductible_amount ?? 0,
                        'is_enabled' => true,
                        'effective_date' => $inceptionDate,
                        'expiry_date' => $expiryDate,
                    ]);
                }
            }
        }

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client application submitted successfully! Policy created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $client->load(['principalMember', 'dependents', 'policies.insuranceCompany']);
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
        return view('clients.edit', compact('client', 'principals'));
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

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
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
