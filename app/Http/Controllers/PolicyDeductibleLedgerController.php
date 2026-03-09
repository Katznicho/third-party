<?php

namespace App\Http\Controllers;

use App\Models\PolicyDeductibleLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PolicyDeductibleLedgerController extends Controller

{
    public function index(Request $request)
    {
        $user =   Auth::user();
        $insuranceCompany = $user->insuranceCompany;

        $query = PolicyDeductibleLedger::with(['policy'])
            ->where('insurance_company_id', $insuranceCompany->id)
            ->orderByDesc('created_at');

        if ($request->filled('policy_number')) {
            $policyNumber = $request->get('policy_number');
            $query->whereHas('policy', function ($q) use ($policyNumber) {
                $q->where('policy_number', 'like', '%' . $policyNumber . '%');
            });
        }



        if ($request->filled('invoice_number')) {
            $invoiceNumber = $request->get('invoice_number');
            $query->where('external_invoice_number', 'like', '%' . $invoiceNumber . '%');
        }

        $ledgers = $query->paginate(20)->withQueryString();

        return view('policy-deductible-ledgers.index', compact('ledgers'));
    }

    

    public function show(PolicyDeductibleLedger $ledger)
    {
        $user = Auth::user();
        $insuranceCompany = $user->insuranceCompany;

        abort_unless($ledger->insurance_company_id === $insuranceCompany->id, 403);

        $ledger->load([
            'policy',
            'authorization.policy',
            'authorization.insuranceCompany',
        ]);

        $authorization = $ledger->authorization;

        return view('policy-deductible-ledgers.show', compact('ledger', 'authorization'));
    }
}

