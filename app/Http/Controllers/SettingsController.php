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
            'require_physical_id' => 'nullable|boolean',
            'enable_method_1' => 'nullable|boolean',
            'enable_method_2' => 'nullable|boolean',
            'enable_method_3' => 'nullable|boolean',
            'enable_method_4' => 'nullable|boolean',
            'name_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'dob_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'id_mismatch_action' => 'required|in:auto_reject,flag_for_review',
            'name_similarity_threshold' => 'required|integer|min:0|max:100',
            'dob_tolerance_days' => 'required|integer|min:0|max:365',
            'phone_otp_expiry_minutes' => 'required|integer|min:1|max:60',
            'email_otp_expiry_minutes' => 'required|integer|min:1|max:60',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update([
            'require_physical_id' => $request->boolean('require_physical_id', true),
            'enable_method_1' => $request->boolean('enable_method_1', true),
            'enable_method_2' => $request->boolean('enable_method_2', false),
            'enable_method_3' => $request->boolean('enable_method_3', false),
            'enable_method_4' => $request->boolean('enable_method_4', false),
            'name_mismatch_action' => $validated['name_mismatch_action'],
            'dob_mismatch_action' => $validated['dob_mismatch_action'],
            'id_mismatch_action' => $validated['id_mismatch_action'],
            'name_similarity_threshold' => $validated['name_similarity_threshold'],
            'dob_tolerance_days' => $validated['dob_tolerance_days'],
            'phone_otp_expiry_minutes' => $validated['phone_otp_expiry_minutes'],
            'email_otp_expiry_minutes' => $validated['email_otp_expiry_minutes'],
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Identity verification settings updated successfully.');
    }
}
