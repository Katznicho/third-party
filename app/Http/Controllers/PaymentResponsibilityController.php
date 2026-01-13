<?php

namespace App\Http\Controllers;

use App\Models\PaymentResponsibility;
use Illuminate\Http\Request;

class PaymentResponsibilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $paymentResponsibilities = PaymentResponsibility::with(['policy', 'policyBenefit', 'preAuthorization', 'transaction'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->whereIn('status', ['pending', 'calculated', 'partially_paid'])
            ->latest()
            ->paginate(15);
            
        return view('payment-responsibilities.index', compact('paymentResponsibilities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment-responsibilities.create');
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
    public function show(PaymentResponsibility $paymentResponsibility)
    {
        return view('payment-responsibilities.show', compact('paymentResponsibility'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentResponsibility $paymentResponsibility)
    {
        return view('payment-responsibilities.edit', compact('paymentResponsibility'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentResponsibility $paymentResponsibility)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentResponsibility $paymentResponsibility)
    {
        //
    }
}
