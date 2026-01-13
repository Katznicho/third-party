<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $policies = Policy::with(['principalMember', 'insuranceCompany'])
            ->where('insurance_company_id', $insuranceCompanyId)
            ->latest()
            ->paginate(15);
            
        return view('policies.index', compact('policies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = \App\Models\Client::where('type', 'principal')->where('is_active', true)->get();
        return view('policies.create', compact('clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'policy_number' => 'required|string|max:255|unique:policies,policy_number',
            'principal_member_id' => 'required|exists:clients,id',
            'plan_type' => 'required|in:Prestige,Executive,Standard Plus,Standard,Regular,Budget',
            'inception_date' => 'required|date',
            'expiry_date' => 'required|date|after:inception_date',
            'desired_start_date' => 'nullable|date',
            'total_premium' => 'required|numeric|min:0',
            'insurance_training_levy' => 'nullable|numeric|min:0',
            'stamp_duty' => 'nullable|numeric|min:0',
            'total_premium_due' => 'required|numeric|min:0',
            'agent_broker_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended,expired,cancelled',
            'is_paid' => 'nullable|boolean',
            'payment_date' => 'nullable|date',
            'has_deductible' => 'nullable|boolean',
            'telemedicine_only' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['insurance_company_id'] = auth()->user()->insurance_company_id;
        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');

        $policy = Policy::create($validated);

        return redirect()->route('policies.index')
            ->with('success', 'Policy created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Policy $policy)
    {
        return view('policies.show', compact('policy'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Policy $policy)
    {
        // Ensure user can only edit policies from their insurance company
        if ($policy->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to policy.');
        }

        $clients = \App\Models\Client::where('type', 'principal')->where('is_active', true)->get();
        $policy->load('principalMember');
        return view('policies.edit', compact('policy', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Policy $policy)
    {
        // Ensure user can only update policies from their insurance company
        if ($policy->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to policy.');
        }

        $validated = $request->validate([
            'policy_number' => 'required|string|max:255|unique:policies,policy_number,' . $policy->id,
            'principal_member_id' => 'required|exists:clients,id',
            'plan_type' => 'required|in:Prestige,Executive,Standard Plus,Standard,Regular,Budget',
            'inception_date' => 'required|date',
            'expiry_date' => 'required|date|after:inception_date',
            'desired_start_date' => 'nullable|date',
            'total_premium' => 'required|numeric|min:0',
            'insurance_training_levy' => 'nullable|numeric|min:0',
            'stamp_duty' => 'nullable|numeric|min:0',
            'total_premium_due' => 'required|numeric|min:0',
            'agent_broker_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended,expired,cancelled',
            'is_paid' => 'nullable|boolean',
            'payment_date' => 'nullable|date',
            'has_deductible' => 'nullable|boolean',
            'telemedicine_only' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');

        $policy->update($validated);

        return redirect()->route('policies.index')
            ->with('success', 'Policy updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Policy $policy)
    {
        // Ensure user can only delete policies from their insurance company
        if ($policy->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to policy.');
        }

        // Check if policy has related records
        if ($policy->transactions()->count() > 0 || $policy->invoices()->count() > 0) {
            return redirect()->route('policies.index')
                ->with('error', 'Cannot delete policy with existing transactions or invoices.');
        }

        $policy->delete();

        return redirect()->route('policies.index')
            ->with('success', 'Policy deleted successfully.');
    }
}
