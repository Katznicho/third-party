<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorCodeEmail;
use Illuminate\Support\Facades\Log;

class VendorCodeController extends Controller
{
    /**
     * Show the form to send vendor code via email
     */
    public function create()
    {
        $user = Auth::user();
        $insuranceCompany = $user->insuranceCompany;

        if (!$insuranceCompany || !$insuranceCompany->code) {
            return redirect()->route('dashboard')
                ->with('error', 'Vendor code not found. Please contact support.');
        }

        return view('vendor-code.create', [
            'vendorCode' => $insuranceCompany->code,
            'vendorName' => $insuranceCompany->name,
        ]);
    }

    /**
     * Send vendor code via email
     */
    public function send(Request $request)
    {
        $user = Auth::user();
        $insuranceCompany = $user->insuranceCompany;

        if (!$insuranceCompany || !$insuranceCompany->code) {
            return redirect()->route('dashboard')
                ->with('error', 'Vendor code not found. Please contact support.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            Mail::to($validated['email'])->send(
                new VendorCodeEmail(
                    $insuranceCompany->code,
                    $insuranceCompany->name,
                    $validated['email'],
                    $validated['message'] ?? null
                )
            );

            Log::info('Vendor code email sent', [
                'vendor_code' => $insuranceCompany->code,
                'vendor_name' => $insuranceCompany->name,
                'recipient_email' => $validated['email'],
                'sent_by' => $user->id,
            ]);

            return redirect()->route('vendor-code.create')
                ->with('success', 'Vendor code has been sent successfully to ' . $validated['email']);
        } catch (\Exception $e) {
            Log::error('Failed to send vendor code email', [
                'error' => $e->getMessage(),
                'recipient_email' => $validated['email'],
            ]);

            return redirect()->route('vendor-code.create')
                ->with('error', 'Failed to send email. Please try again later.')
                ->withInput();
        }
    }
}
