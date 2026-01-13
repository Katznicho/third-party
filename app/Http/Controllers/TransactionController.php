<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $transactions = Transaction::with(['policy', 'client', 'preAuthorization'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->latest()
            ->paginate(15);
            
        return view('transactions.index', compact('transactions'));
    }

    /**
     * Display outstanding transactions.
     */
    public function outstanding()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $transactions = Transaction::with(['policy', 'client'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->where(function($query) {
                $query->where('transaction_status', '!=', 'cleared')
                      ->orWhereNull('transaction_status');
            })
            ->latest()
            ->paginate(15);
            
        return view('transactions.outstanding', compact('transactions'));
    }

    /**
     * Display cleared transactions.
     */
    public function cleared()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $transactions = Transaction::with(['policy', 'client'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->where('transaction_status', 'cleared')
            ->latest()
            ->paginate(15);
            
        return view('transactions.cleared', compact('transactions'));
    }
}
