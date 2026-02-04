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
}
