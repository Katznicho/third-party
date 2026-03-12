<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InsuranceCompany;
use App\Models\PreAuthorizationApprover;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $insuranceCompany->load('preAuthorizationApprovers');
        $users = User::where('insurance_company_id', $insuranceCompany->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('settings.index', compact('insuranceCompany', 'users'));
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
            'payment_responsibility_collection' => 'nullable|in:immediate,later',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update($validated);

        $tab = $request->input('current_tab', 'policy-number');
        return redirect()->route('settings.index', ['tab' => $tab])
            ->with('success', 'Policy number generation settings updated successfully.');
    }

    /**
     * Update payment settings (allowed methods + grace period per method).
     */
    public function updatePaymentSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $allowedKeys = array_keys(InsuranceCompany::getPaymentMethodOptions());
        $validated = $request->validate([
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'string|in:' . implode(',', $allowedKeys),
            // grace_periods kept for backward compatibility but not exposed in UI anymore
            'grace_periods' => 'nullable|array',
            'grace_periods.*' => 'nullable|integer|min:0|max:365',
        ]);

        $insuranceCompany = $user->insuranceCompany;

        // Keep existing grace periods (or set to 0) – they are no longer edited in UI
        $existingGrace = $insuranceCompany->payment_grace_periods ?? [];
        $gracePeriods = $existingGrace;
        if (isset($validated['grace_periods'])) {
            $gracePeriods = [];
            foreach ($allowedKeys as $key) {
                $gracePeriods[$key] = isset($validated['grace_periods'][$key])
                    ? (int) $validated['grace_periods'][$key]
                    : 0;
            }
        }

        $insuranceCompany->update([
            'payment_methods' => $validated['payment_methods'] ?? [],
            'payment_grace_periods' => $gracePeriods,
        ]);

        return redirect()->route('settings.index', ['tab' => 'payment'])
            ->with('success', 'Payment settings updated successfully.');
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

        $tab = $request->input('current_tab', 'deductible');
        return redirect()->route('settings.index', ['tab' => $tab])
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

        $tab = $request->input('current_tab', 'client-fields');
        return redirect()->route('settings.index', ['tab' => $tab])
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
        
        // Build update array, only including fields that exist in the database
        $updateData = [
            'name_mismatch_action' => $validated['name_mismatch_action'],
            'dob_mismatch_action' => $validated['dob_mismatch_action'],
            'id_mismatch_action' => $validated['id_mismatch_action'],
            'name_similarity_threshold' => $validated['name_similarity_threshold'],
            'dob_tolerance_days' => $validated['dob_tolerance_days'],
            'phone_otp_expiry_minutes' => $validated['phone_otp_expiry_minutes'],
            'email_otp_expiry_minutes' => $validated['email_otp_expiry_minutes'],
        ];
        
        // Only add these fields if the columns exist
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('insurance_companies');
        if (in_array('require_physical_id', $columns)) {
            $updateData['require_physical_id'] = $request->boolean('require_physical_id', true);
        }
        if (in_array('enable_method_1', $columns)) {
            $updateData['enable_method_1'] = $request->boolean('enable_method_1', true);
        }
        if (in_array('enable_method_2', $columns)) {
            $updateData['enable_method_2'] = $request->boolean('enable_method_2', false);
        }
        if (in_array('enable_method_3', $columns)) {
            $updateData['enable_method_3'] = $request->boolean('enable_method_3', false);
        }
        if (in_array('enable_method_4', $columns)) {
            $updateData['enable_method_4'] = $request->boolean('enable_method_4', false);
        }
        
        $insuranceCompany->update($updateData);

        $tab = $request->input('current_tab', 'verification');
        return redirect()->route('settings.index', ['tab' => $tab])
            ->with('success', 'Identity verification settings updated successfully.');
    }

    /**
     * Update authorization settings
     */
    public function updateAuthorizationSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'enable_auto_authorization' => 'nullable|boolean',
            'auto_approve_max_amount' => 'nullable|numeric|min:0',
            'auto_reject_min_amount' => 'nullable|numeric|min:0',
            'require_manual_review_above_amount' => 'nullable|boolean',
            'manual_review_threshold_amount' => 'nullable|numeric|min:0',
            'invoice_authorization_levels' => 'required|integer|min:1|max:3',
            'approvers_level_1' => 'nullable|array',
            'approvers_level_1.*' => 'integer|exists:users,id',
            'approvers_level_2' => 'nullable|array',
            'approvers_level_2.*' => 'integer|exists:users,id',
            'approvers_level_3' => 'nullable|array',
            'approvers_level_3.*' => 'integer|exists:users,id',
            'invoice_clearing_trigger' => 'nullable|string|in:on_client_portion_paid,on_full_payment,on_fulfillment,manual',
            'authorization_valid_days' => 'nullable|integer|min:1|max:365',
            'authorization_valid_unit' => 'nullable|string|in:minutes,hours,days',
            'require_reauthorize_if_edited' => 'nullable|boolean',
        ]);

        $insuranceCompany = $user->insuranceCompany;

        DB::transaction(function () use ($insuranceCompany, $validated, $request) {
            $insuranceCompany->update([
                'enable_auto_authorization' => $request->boolean('enable_auto_authorization', true),
                'auto_approve_max_amount' => $validated['auto_approve_max_amount'] ?? null,
                'auto_reject_min_amount' => $validated['auto_reject_min_amount'] ?? null,
                'require_manual_review_above_amount' => $request->boolean('require_manual_review_above_amount', true),
                'manual_review_threshold_amount' => $validated['manual_review_threshold_amount'] ?? null,
                'invoice_authorization_levels' => (int) $validated['invoice_authorization_levels'],
                'invoice_clearing_trigger' => $validated['invoice_clearing_trigger'] ?? null,
                'authorization_valid_days' => $validated['authorization_valid_days'] ?? null,
                'authorization_valid_unit' => $validated['authorization_valid_unit'] ?? 'days',
                'require_reauthorize_if_edited' => $request->boolean('require_reauthorize_if_edited', false),
            ]);

            PreAuthorizationApprover::where('insurance_company_id', $insuranceCompany->id)->delete();
            $levels = (int) $validated['invoice_authorization_levels'];
            for ($lvl = 1; $lvl <= $levels; $lvl++) {
                $userIds = $validated["approvers_level_{$lvl}"] ?? [];
                foreach ($userIds as $userId) {
                    PreAuthorizationApprover::create([
                        'insurance_company_id' => $insuranceCompany->id,
                        'user_id' => (int) $userId,
                        'level' => $lvl,
                    ]);
                }
            }
        });

        $tab = $request->input('current_tab', 'authorization');
        return redirect()->route('settings.index', ['tab' => $tab])
            ->with('success', 'Authorization settings updated successfully.');
    }

    /**
     * Update account number generation settings
     */
    public function updateAccountNumberSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->insuranceCompany) {
            return redirect()->back()->with('error', 'You must be associated with an insurance company.');
        }

        $validated = $request->validate([
            'account_number_format' => 'required|string|max:255',
            'account_number_random_length' => 'required|integer|min:1|max:12',
            'account_number_random_type' => 'required|in:numeric,alphanumeric,alphabetic',
            'account_number_company_code_length' => 'required|integer|min:1|max:8',
        ]);

        $insuranceCompany = $user->insuranceCompany;
        $insuranceCompany->update($validated);

        $tab = $request->input('current_tab', 'account-number');
        return redirect()->route('settings.index', ['tab' => $tab])
            ->with('success', 'Account number generation settings updated successfully.');
    }
}
