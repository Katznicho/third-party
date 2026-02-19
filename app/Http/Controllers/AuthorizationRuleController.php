<?php

namespace App\Http\Controllers;

use App\Models\AuthorizationRule;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthorizationRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $rules = AuthorizationRule::forInsuranceCompany($insuranceCompanyId)
            ->byPriority()
            ->latest()
            ->paginate(20);
        
        return view('authorization-rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        $serviceCategories = \App\Models\ServiceCategory::all();
        $policyTypes = \App\Models\Policy::distinct()->pluck('policy_type')->filter();
        
        return view('authorization-rules.create', compact('serviceCategories', 'policyTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:amount,service_category,policy_type,client_tier,time_based,risk_based,combined',
            'action' => 'required|in:auto_approve,auto_reject,flag_for_review,partially_approve',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'boolean',
            
            // Amount-based conditions
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'exact_amount' => 'nullable|numeric|min:0',
            
            // Service category conditions
            'service_category_ids' => 'nullable|array',
            'service_category_ids.*' => 'exists:service_categories,id',
            
            // Policy type conditions
            'policy_types' => 'nullable|array',
            'policy_types.*' => 'string',
            
            // Client tier conditions
            'client_tiers' => 'nullable|array',
            'client_tiers.*' => 'string',
            
            // Time-based conditions
            'business_hours_only' => 'nullable|boolean',
            'allowed_hours' => 'nullable|array',
            'allowed_hours.*' => 'integer|min:0|max:23',
            
            // Partial approval
            'partial_approval_percentage' => 'nullable|numeric|min:0|max:100|required_if:action,partially_approve',
            'partial_approval_amount' => 'nullable|numeric|min:0|required_if:action,partially_approve',
        ]);

        // Build conditions array based on rule type
        $conditions = $this->buildConditions($validated);
        
        $rule = AuthorizationRule::create([
            'insurance_company_id' => $insuranceCompanyId,
            'rule_name' => $validated['rule_name'],
            'description' => $validated['description'] ?? null,
            'rule_type' => $validated['rule_type'],
            'conditions' => $conditions,
            'action' => $validated['action'],
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
            'partial_approval_percentage' => $validated['partial_approval_percentage'] ?? null,
            'partial_approval_amount' => $validated['partial_approval_amount'] ?? null,
        ]);

        Log::info('Authorization rule created', [
            'rule_id' => $rule->id,
            'rule_name' => $rule->rule_name,
            'insurance_company_id' => $insuranceCompanyId,
        ]);

        return redirect()->route('authorization-rules.index')
            ->with('success', 'Authorization rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AuthorizationRule $authorizationRule)
    {
        $authorizationRule->load(['insuranceCompany', 'auditLogs' => function($query) {
            $query->latest()->limit(10);
        }]);
        
        return view('authorization-rules.show', compact('authorizationRule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuthorizationRule $authorizationRule)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only edit their own company's rules
        if ($authorizationRule->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        $serviceCategories = \App\Models\ServiceCategory::all();
        $policyTypes = \App\Models\Policy::distinct()->pluck('policy_type')->filter();
        $conditions = $authorizationRule->conditions ?? [];
        
        return view('authorization-rules.edit', compact('authorizationRule', 'serviceCategories', 'policyTypes', 'conditions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AuthorizationRule $authorizationRule)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only update their own company's rules
        if ($authorizationRule->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:amount,service_category,policy_type,client_tier,time_based,risk_based,combined',
            'action' => 'required|in:auto_approve,auto_reject,flag_for_review,partially_approve',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'boolean',
            
            // Amount-based conditions
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'exact_amount' => 'nullable|numeric|min:0',
            
            // Service category conditions
            'service_category_ids' => 'nullable|array',
            'service_category_ids.*' => 'exists:service_categories,id',
            
            // Policy type conditions
            'policy_types' => 'nullable|array',
            'policy_types.*' => 'string',
            
            // Client tier conditions
            'client_tiers' => 'nullable|array',
            'client_tiers.*' => 'string',
            
            // Time-based conditions
            'business_hours_only' => 'nullable|boolean',
            'allowed_hours' => 'nullable|array',
            'allowed_hours.*' => 'integer|min:0|max:23',
            
            // Partial approval
            'partial_approval_percentage' => 'nullable|numeric|min:0|max:100|required_if:action,partially_approve',
            'partial_approval_amount' => 'nullable|numeric|min:0|required_if:action,partially_approve',
        ]);

        $conditions = $this->buildConditions($validated);
        
        $authorizationRule->update([
            'rule_name' => $validated['rule_name'],
            'description' => $validated['description'] ?? null,
            'rule_type' => $validated['rule_type'],
            'conditions' => $conditions,
            'action' => $validated['action'],
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
            'partial_approval_percentage' => $validated['partial_approval_percentage'] ?? null,
            'partial_approval_amount' => $validated['partial_approval_amount'] ?? null,
        ]);

        Log::info('Authorization rule updated', [
            'rule_id' => $authorizationRule->id,
            'rule_name' => $authorizationRule->rule_name,
        ]);

        return redirect()->route('authorization-rules.index')
            ->with('success', 'Authorization rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuthorizationRule $authorizationRule)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only delete their own company's rules
        if ($authorizationRule->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        $authorizationRule->delete();

        Log::info('Authorization rule deleted', [
            'rule_id' => $authorizationRule->id,
            'rule_name' => $authorizationRule->rule_name,
        ]);

        return redirect()->route('authorization-rules.index')
            ->with('success', 'Authorization rule deleted successfully.');
    }

    /**
     * Build conditions array from validated data
     */
    protected function buildConditions(array $validated): array
    {
        $conditions = [];

        switch ($validated['rule_type']) {
            case 'amount':
                if (isset($validated['min_amount'])) {
                    $conditions['min_amount'] = $validated['min_amount'];
                }
                if (isset($validated['max_amount'])) {
                    $conditions['max_amount'] = $validated['max_amount'];
                }
                if (isset($validated['exact_amount'])) {
                    $conditions['exact_amount'] = $validated['exact_amount'];
                }
                break;

            case 'service_category':
                if (isset($validated['service_category_ids'])) {
                    $conditions['service_category_ids'] = $validated['service_category_ids'];
                }
                break;

            case 'policy_type':
                if (isset($validated['policy_types'])) {
                    $conditions['policy_types'] = $validated['policy_types'];
                }
                break;

            case 'client_tier':
                if (isset($validated['client_tiers'])) {
                    $conditions['client_tiers'] = $validated['client_tiers'];
                }
                break;

            case 'time_based':
                if (isset($validated['business_hours_only'])) {
                    $conditions['business_hours_only'] = (bool) $validated['business_hours_only'];
                }
                if (isset($validated['allowed_hours'])) {
                    $conditions['allowed_hours'] = $validated['allowed_hours'];
                }
                break;

            case 'combined':
                // Build combined conditions
                $conditions['operator'] = $validated['operator'] ?? 'AND';
                if (isset($validated['min_amount']) || isset($validated['max_amount'])) {
                    $conditions['amount'] = [];
                    if (isset($validated['min_amount'])) {
                        $conditions['amount']['min_amount'] = $validated['min_amount'];
                    }
                    if (isset($validated['max_amount'])) {
                        $conditions['amount']['max_amount'] = $validated['max_amount'];
                    }
                }
                if (isset($validated['service_category_ids'])) {
                    $conditions['service_category'] = ['service_category_ids' => $validated['service_category_ids']];
                }
                // Add more combined conditions as needed
                break;
        }

        return $conditions;
    }
}
