<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InsuranceCompany;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company to access settings.');
        }

        $insuranceCompany = $user->insuranceCompany;
        
        return view('settings.index', compact('insuranceCompany'));
    }

    /**
     * Update policy number generation settings
     */
    public function updatePolicyNumberSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'policy_number_format' => 'required|string|max:255',
            'policy_number_random_length' => 'required|integer|min:3|max:12',
            'policy_number_random_type' => 'required|in:alphanumeric,numeric,alphabetic',
            'policy_number_company_code_length' => 'required|integer|min:1|max:8',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update($validated);

        return redirect()->route('settings.index')
            ->with('success', 'Policy number generation settings updated successfully.');
    }

    /**
     * Update deductible contribution settings
     */
    public function updateDeductibleContributionSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'copay_contributes_to_deductible' => 'nullable|boolean',
            'coinsurance_contributes_to_deductible' => 'nullable|boolean',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update([
            'copay_contributes_to_deductible' => $request->boolean('copay_contributes_to_deductible', false),
            'coinsurance_contributes_to_deductible' => $request->boolean('coinsurance_contributes_to_deductible', false),
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Deductible contribution settings updated successfully.');
    }

    /**
     * Update required client fields settings
     */
    public function updateRequiredClientFields(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $insuranceCompany = $user->insuranceCompany;
        $defaultFields = InsuranceCompany::getDefaultRequiredFields();
        
        // Build required fields array from request
        $requiredFields = [];
        foreach ($defaultFields as $fieldName => $defaultValue) {
            $requiredFields[$fieldName] = $request->boolean("required_fields.{$fieldName}", $defaultValue);
        }
        
        $insuranceCompany->update([
            'required_client_fields' => $requiredFields,
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Required client fields settings updated successfully.');
    }

    /**
     * Update identity verification settings
     */
    public function updateVerificationSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'enable_name_dob_verification' => 'nullable|boolean',
            'enable_id_passport_verification' => 'nullable|boolean',
            'enable_phone_verification' => 'nullable|boolean',
            'enable_email_verification' => 'nullable|boolean',
            'name_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'dob_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'id_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'name_similarity_threshold' => 'required|integer|min:0|max:100',
            'dob_tolerance_days' => 'required|integer|min:0|max:365',
            'enable_visit_verification' => 'nullable|boolean',
            'visit_verification_validity_days' => 'required|integer|min:1|max:365',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update([
            'enable_name_dob_verification' => $request->boolean('enable_name_dob_verification', false),
            'enable_id_passport_verification' => $request->boolean('enable_id_passport_verification', false),
            'enable_phone_verification' => $request->boolean('enable_phone_verification', false),
            'enable_email_verification' => $request->boolean('enable_email_verification', false),
            'name_mismatch_action' => $validated['name_mismatch_action'],
            'dob_mismatch_action' => $validated['dob_mismatch_action'],
            'id_mismatch_action' => $validated['id_mismatch_action'],
            'name_similarity_threshold' => $validated['name_similarity_threshold'],
            'dob_tolerance_days' => $validated['dob_tolerance_days'],
            'enable_visit_verification' => $request->boolean('enable_visit_verification', false),
            'visit_verification_validity_days' => $validated['visit_verification_validity_days'],
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Identity verification settings updated successfully.');
    }
}
