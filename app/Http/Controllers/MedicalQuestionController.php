<?php

namespace App\Http\Controllers;

use App\Models\MedicalQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company to manage medical questions.');
        }

        $questions = MedicalQuestion::where('insurance_company_id', $user->insurance_company_id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        return view('medical-questions.index', compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('medical-questions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $hasMonetaryImpact = $request->boolean('has_monetary_impact');
        
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:yes_no,text,date,number',
            'has_exclusion_list' => 'nullable',
            'exclusion_keywords' => 'nullable|string', // Comma-separated keywords
            'requires_additional_info' => 'nullable',
            'additional_info_type' => 'nullable|in:text,date,table',
            'additional_info_label' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            // Monetary Impact
            'has_monetary_impact' => 'nullable|boolean',
            'monetary_impact_type' => $hasMonetaryImpact ? 'required|in:premium_adjustment,deductible_adjustment,coverage_limit_adjustment' : 'nullable|in:premium_adjustment,deductible_adjustment,coverage_limit_adjustment,none',
            'monetary_impact_amount' => 'nullable|numeric|min:0',
            'monetary_impact_is_percentage' => 'nullable|boolean',
            'monetary_impact_applies_to_response' => $hasMonetaryImpact ? 'required|string|max:255' : 'nullable|string|max:255',
            'monetary_impact_description' => 'nullable|string',
        ], [
            'monetary_impact_type.required' => 'Please select a valid Impact Type when "Has Monetary Impact" is enabled.',
            'monetary_impact_type.in' => 'The selected Impact Type is invalid. Please choose Premium Adjustment, Deductible Adjustment, or Coverage Limit Adjustment.',
        ]);

        // Handle boolean checkboxes
        $validated['has_exclusion_list'] = $request->boolean('has_exclusion_list');
        $validated['requires_additional_info'] = $request->boolean('requires_additional_info');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['has_monetary_impact'] = $request->boolean('has_monetary_impact');
        $validated['monetary_impact_is_percentage'] = $request->boolean('monetary_impact_is_percentage');
        
        // Handle monetary impact logic correctly
        if (!$validated['has_monetary_impact']) {
            // If monetary impact is disabled, set type to 'none'
            $validated['monetary_impact_type'] = 'none';
            $validated['monetary_impact_amount'] = null;
            $validated['monetary_impact_is_percentage'] = false;
            $validated['monetary_impact_applies_to_response'] = null;
            $validated['monetary_impact_description'] = null;
        } else {
            // If monetary impact is enabled, ensure a valid type is set (not 'none')
            if (!isset($validated['monetary_impact_type']) || $validated['monetary_impact_type'] === 'none') {
                // If type is 'none' but has_monetary_impact is true, default to premium_adjustment
                $validated['monetary_impact_type'] = 'premium_adjustment';
            }
            // Ensure applies_to_response has a default value if not set
            if (empty($validated['monetary_impact_applies_to_response'])) {
                $validated['monetary_impact_applies_to_response'] = 'yes';
            }
        }

        // Convert comma-separated keywords to array
        if (!empty($validated['exclusion_keywords'])) {
            $keywords = array_map('trim', explode(',', $validated['exclusion_keywords']));
            $validated['exclusion_keywords'] = array_filter($keywords);
        } else {
            $validated['exclusion_keywords'] = [];
        }

        // Set default order if not provided
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->withInput()->withErrors(['error' => 'You must be associated with an insurance company to create medical questions.']);
        }

        if (!isset($validated['order'])) {
            $maxOrder = MedicalQuestion::where('insurance_company_id', $user->insurance_company_id)->max('order');
            $validated['order'] = ($maxOrder ?? 0) + 1;
        }

        // Set insurance company ID
        $validated['insurance_company_id'] = $user->insurance_company_id;

        MedicalQuestion::create($validated);

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalQuestion $medicalQuestion)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $medicalQuestion->insurance_company_id !== $user->insurance_company_id) {
            abort(403, 'Unauthorized access to this medical question.');
        }
        return view('medical-questions.show', compact('medicalQuestion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicalQuestion $medicalQuestion)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $medicalQuestion->insurance_company_id !== $user->insurance_company_id) {
            abort(403, 'Unauthorized access to this medical question.');
        }
        return view('medical-questions.edit', compact('medicalQuestion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicalQuestion $medicalQuestion)
    {
        $hasMonetaryImpact = $request->boolean('has_monetary_impact');
        
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:yes_no,text,date,number',
            'has_exclusion_list' => 'nullable',
            'exclusion_keywords' => 'nullable|string', // Comma-separated keywords
            'requires_additional_info' => 'nullable',
            'additional_info_type' => 'nullable|in:text,date,table',
            'additional_info_label' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            // Monetary Impact
            'has_monetary_impact' => 'nullable|boolean',
            'monetary_impact_type' => $hasMonetaryImpact ? 'required|in:premium_adjustment,deductible_adjustment,coverage_limit_adjustment' : 'nullable|in:premium_adjustment,deductible_adjustment,coverage_limit_adjustment,none',
            'monetary_impact_amount' => 'nullable|numeric|min:0',
            'monetary_impact_is_percentage' => 'nullable|boolean',
            'monetary_impact_applies_to_response' => $hasMonetaryImpact ? 'required|string|max:255' : 'nullable|string|max:255',
            'monetary_impact_description' => 'nullable|string',
        ], [
            'monetary_impact_type.required' => 'Please select a valid Impact Type when "Has Monetary Impact" is enabled.',
            'monetary_impact_type.in' => 'The selected Impact Type is invalid. Please choose Premium Adjustment, Deductible Adjustment, or Coverage Limit Adjustment.',
        ]);

        // Handle boolean checkboxes
        $validated['has_exclusion_list'] = $request->boolean('has_exclusion_list');
        $validated['requires_additional_info'] = $request->boolean('requires_additional_info');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['has_monetary_impact'] = $request->boolean('has_monetary_impact');
        $validated['monetary_impact_is_percentage'] = $request->boolean('monetary_impact_is_percentage');
        
        // Handle monetary impact logic correctly
        if (!$validated['has_monetary_impact']) {
            // If monetary impact is disabled, set type to 'none'
            $validated['monetary_impact_type'] = 'none';
            $validated['monetary_impact_amount'] = null;
            $validated['monetary_impact_is_percentage'] = false;
            $validated['monetary_impact_applies_to_response'] = null;
            $validated['monetary_impact_description'] = null;
        } else {
            // If monetary impact is enabled, ensure a valid type is set (not 'none')
            if (!isset($validated['monetary_impact_type']) || $validated['monetary_impact_type'] === 'none') {
                // If type is 'none' but has_monetary_impact is true, default to premium_adjustment
                $validated['monetary_impact_type'] = 'premium_adjustment';
            }
            // Ensure applies_to_response has a default value if not set
            if (empty($validated['monetary_impact_applies_to_response'])) {
                $validated['monetary_impact_applies_to_response'] = 'yes';
            }
        }

        // Convert comma-separated keywords to array
        if (!empty($validated['exclusion_keywords'])) {
            $keywords = array_map('trim', explode(',', $validated['exclusion_keywords']));
            $validated['exclusion_keywords'] = array_filter($keywords);
        } else {
            $validated['exclusion_keywords'] = [];
        }

        $user = auth()->user();
        if (!$user->insuranceCompany || $medicalQuestion->insurance_company_id !== $user->insurance_company_id) {
            abort(403, 'Unauthorized access to this medical question.');
        }

        $medicalQuestion->update($validated);

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalQuestion $medicalQuestion)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany || $medicalQuestion->insurance_company_id !== $user->insurance_company_id) {
            abort(403, 'Unauthorized access to this medical question.');
        }

        $medicalQuestion->delete();

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question deleted successfully.');
    }

    /**
     * Update question order (for drag and drop reordering)
     */
    public function updateOrder(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:medical_questions,id',
            'questions.*.order' => 'required|integer',
        ]);

        foreach ($request->questions as $questionData) {
            MedicalQuestion::where('id', $questionData['id'])
                ->where('insurance_company_id', $user->insurance_company_id)
                ->update(['order' => $questionData['order']]);
        }

        return response()->json(['success' => true]);
    }
}
