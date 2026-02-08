<?php

namespace App\Http\Controllers;

use App\Models\CoverageDecisionMatrix;
use App\Models\InsuranceCompany;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoverageDecisionMatrixController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        $insuranceCompany = $user->insuranceCompany;
        $rules = CoverageDecisionMatrix::where('insurance_company_id', $insuranceCompany->id)
            ->orderedByPriority()
            ->get();

        return view('settings.coverage-decision-matrix.index', compact('insuranceCompany', 'rules'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => 'required|in:service_category_not_covered,service_category_coverage_limit_exceeded,cost_threshold_exceeded,keyword_match,procedure_type,visit_type_not_covered,custom_condition',
            'condition_config' => 'nullable|string',
            'action' => 'required|in:auto_reject,flag_for_review,require_pre_authorization',
            'rejection_message' => 'nullable|string',
            'review_notes_template' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $validated['insurance_company_id'] = $insuranceCompany->id;
        $validated['is_active'] = $request->boolean('is_active', true);
        
        // Parse condition_config JSON string to array
        if (isset($validated['condition_config']) && is_string($validated['condition_config'])) {
            $validated['condition_config'] = json_decode($validated['condition_config'], true) ?? [];
        }

        CoverageDecisionMatrix::create($validated);

        return redirect()->route('settings.coverage-decision-matrix.index')
            ->with('success', 'Decision rule created successfully.');
    }

    public function update(Request $request, CoverageDecisionMatrix $coverageDecisionMatrix)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $coverageDecisionMatrix->insurance_company_id != $user->insurance_company_id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => 'required|in:service_category_not_covered,service_category_coverage_limit_exceeded,cost_threshold_exceeded,keyword_match,procedure_type,visit_type_not_covered,custom_condition',
            'condition_config' => 'nullable|string',
            'action' => 'required|in:auto_reject,flag_for_review,require_pre_authorization',
            'rejection_message' => 'nullable|string',
            'review_notes_template' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        
        // Parse condition_config JSON string to array
        if (isset($validated['condition_config']) && is_string($validated['condition_config'])) {
            $validated['condition_config'] = json_decode($validated['condition_config'], true) ?? [];
        }
        
        $coverageDecisionMatrix->update($validated);

        return redirect()->route('settings.coverage-decision-matrix.index')
            ->with('success', 'Decision rule updated successfully.');
    }

    public function destroy(CoverageDecisionMatrix $coverageDecisionMatrix)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $coverageDecisionMatrix->insurance_company_id != $user->insurance_company_id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $coverageDecisionMatrix->delete();

        return redirect()->route('settings.coverage-decision-matrix.index')
            ->with('success', 'Decision rule deleted successfully.');
    }
}
