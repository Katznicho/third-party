<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Get payments that belong to policies of this insurance company
        // Also include payments from Kashtre invoices (created by current user, so they're for this insurance company)
        $payments = Payment::with(['invoice', 'policy', 'client'])
            ->where(function($query) use ($insuranceCompanyId) {
                $query->whereHas('policy', function($q) use ($insuranceCompanyId) {
                    $q->where('insurance_company_id', $insuranceCompanyId);
                })
                ->orWhere('processed_by', auth()->id()); // Payments created by current user (from Kashtre invoices)
            })
            ->latest()
            ->paginate(15);
            
        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        return view('payments.edit', compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
