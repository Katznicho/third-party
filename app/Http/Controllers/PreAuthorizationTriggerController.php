<?php

namespace App\Http\Controllers;

use App\Models\PreAuthorizationTrigger;
use App\Models\InsuranceCompany;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class PreAuthorizationTriggerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company.');
        }

        $insuranceCompany = $user->insuranceCompany;
        $triggers = PreAuthorizationTrigger::where('insurance_company_id', $insuranceCompany->id)
            ->with('serviceCategory')
            ->orderedByPriority()
            ->get();

        $serviceCategories = ServiceCategory::orderBy('name')->get();

        return view('settings.pre-authorization-triggers.index', compact('insuranceCompany', 'triggers', 'serviceCategories'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'trigger_type' => 'required|in:high_cost_service,special_procedure,keyword_match,service_category,cost_threshold,custom',
            'trigger_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'cost_threshold' => 'nullable|numeric|min:0',
            'keywords' => 'nullable',
            'auto_create_preauth' => 'nullable|boolean',
            'require_manual_approval' => 'nullable|boolean',
            'auto_approval_limit' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle keywords - can be JSON string or array
        if ($request->has('keywords')) {
            if (is_string($request->keywords)) {
                $keywords = json_decode($request->keywords, true);
                $validated['keywords'] = is_array($keywords) ? array_filter(array_map('trim', $keywords)) : null;
            } else {
                $validated['keywords'] = is_array($request->keywords) ? array_filter(array_map('trim', $request->keywords)) : null;
            }
        }

        $insuranceCompany = $user->insuranceCompany;
        $validated['insurance_company_id'] = $insuranceCompany->id;
        $validated['auto_create_preauth'] = $request->boolean('auto_create_preauth', false);
        $validated['require_manual_approval'] = $request->boolean('require_manual_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        PreAuthorizationTrigger::create($validated);

        return redirect()->route('settings.pre-authorization-triggers.index')
            ->with('success', 'Pre-authorization trigger created successfully.');
    }

    public function update(Request $request, PreAuthorizationTrigger $preAuthorizationTrigger)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $preAuthorizationTrigger->insurance_company_id != $user->insurance_company_id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'trigger_type' => 'required|in:high_cost_service,special_procedure,keyword_match,service_category,cost_threshold,custom',
            'trigger_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'cost_threshold' => 'nullable|numeric|min:0',
            'keywords' => 'nullable',
            'auto_create_preauth' => 'nullable|boolean',
            'require_manual_approval' => 'nullable|boolean',
            'auto_approval_limit' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle keywords - can be JSON string or array
        if ($request->has('keywords')) {
            if (is_string($request->keywords)) {
                $keywords = json_decode($request->keywords, true);
                $validated['keywords'] = is_array($keywords) ? array_filter(array_map('trim', $keywords)) : null;
            } else {
                $validated['keywords'] = is_array($request->keywords) ? array_filter(array_map('trim', $request->keywords)) : null;
            }
        }

        $validated['auto_create_preauth'] = $request->boolean('auto_create_preauth', false);
        $validated['require_manual_approval'] = $request->boolean('require_manual_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $preAuthorizationTrigger->update($validated);

        return redirect()->route('settings.pre-authorization-triggers.index')
            ->with('success', 'Pre-authorization trigger updated successfully.');
    }

    public function destroy(PreAuthorizationTrigger $preAuthorizationTrigger)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $preAuthorizationTrigger->insurance_company_id != $user->insurance_company_id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $preAuthorizationTrigger->delete();

        return redirect()->route('settings.pre-authorization-triggers.index')
            ->with('success', 'Pre-authorization trigger deleted successfully.');
    }
}
