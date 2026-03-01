<?php

namespace App\Http\Controllers;

use App\Models\InsuranceAuthorization;
use Illuminate\Http\Request;

class AuthorizationCodeController extends Controller
{
    /**
     * List all authorization codes (invoice authorizations from Kashtre) for the third party to track.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->insurance_company_id) {
            return redirect()->route('dashboard')->with('error', 'You must be associated with an insurance company to view authorization codes.');
        }

        $query = InsuranceAuthorization::where('insurance_company_id', $user->insurance_company_id)
            ->with(['policy:id,policy_number', 'insuranceCompany:id,name,code'])
            ->orderByDesc('requested_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('authorization_reference', 'like', "%{$search}%")
                    ->orWhere('confirmation_code', 'like', "%{$search}%")
                    ->orWhere('external_invoice_number', 'like', "%{$search}%")
                    ->orWhere('kashtre_invoice_id', 'like', "%{$search}%");
            });
        }

        $authorizations = $query->paginate(20)->withQueryString();

        return view('authorization-codes.index', compact('authorizations'));
    }
}
