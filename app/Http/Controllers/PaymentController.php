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
        $userId = auth()->id();
        
        // Simplified query: Show all payments created by current user OR linked to policies of this insurance company
        // This ensures we catch all payments regardless of how they were created
        $payments = Payment::with(['invoice', 'policy', 'client'])
            ->where(function($query) use ($insuranceCompanyId, $userId) {
                // Payments created by current user (from Kashtre invoices or manual creation)
                $query->where('processed_by', $userId)
                // OR payments linked to a policy of the current insurance company
                ->orWhereHas('policy', function($q) use ($insuranceCompanyId) {
                    $q->where('insurance_company_id', $insuranceCompanyId);
                })
                // OR payments with insurance_company_id in metadata (using JSON path for better compatibility)
                ->orWhere(function($q) use ($insuranceCompanyId) {
                    $q->whereNotNull('payment_metadata')
                      ->whereRaw('JSON_EXTRACT(payment_metadata, "$.insurance_company_id") = ?', [$insuranceCompanyId]);
                });
            })
            ->latest()
            ->paginate(15);
            
        // Debug: Log the query and results
        \Log::info('Payments query', [
            'insurance_company_id' => $insuranceCompanyId,
            'user_id' => $userId,
            'count' => $payments->total(),
            'payments' => $payments->map(function($p) {
                return [
                    'id' => $p->id,
                    'payment_reference' => $p->payment_reference,
                    'processed_by' => $p->processed_by,
                    'policy_id' => $p->policy_id,
                    'payment_metadata' => $p->payment_metadata,
                ];
            })->toArray(),
        ]);
            
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
        // Eager load relationships
        $payment->load(['invoice', 'policy', 'client', 'processedBy']);
        
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
