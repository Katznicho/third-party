<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Policy;
use App\Services\PaymentCompletionService;
use App\Services\RecordClientPortionService;
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

    /**
     * Mark a premium payment as received (for Bank, Cash, Card etc. – manual recording).
     * Requires a reason. Updates payment to completed and activates the policy.
     */
    public function markReceived(Request $request, Payment $payment)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;

        if ($payment->payment_type !== 'premium_payment') {
            return redirect()->back()->with('error', 'This action only applies to premium payments.');
        }
        if ($payment->status !== 'pending') {
            return redirect()->back()->with('error', 'This payment is not pending.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Please provide a reason for marking this payment as received.',
        ]);

        $payment->load('policy');
        if (!$payment->policy || $payment->policy->insurance_company_id != $insuranceCompanyId) {
            abort(403, 'You do not have access to this payment.');
        }

        $reason = trim($request->input('reason'));
        $existingNotes = $payment->payment_notes ? trim($payment->payment_notes) . "\n" : '';
        $newNotes = $existingNotes . 'Marked as received: ' . $reason;

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $newNotes) {
            $amount = $payment->amount;
            $payment->update([
                'status' => 'completed',
                'paid_amount' => $amount,
                'balance_amount' => 0,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
                'payment_notes' => $newNotes,
            ]);

            // Create transaction and update client account so payment appears on client account
            PaymentCompletionService::ensureTransactionAndAccountForCompletedPayment($payment->fresh());

            $payment->policy->update([
                'status' => 'active',
                'is_paid' => true,
                'payment_date' => now(),
            ]);
        });

        return redirect()->route('clients.show', $payment->client_id)
            ->with('success', 'Payment marked as received. Policy is now active.');
    }

    /**
     * Show form to record a client-portion payment (e.g. after collecting via mobile money in Kashtre).
     * Recording here makes the payment appear in: Payments, client account statement, balance statement, third-party vendor page.
     */
    public function recordClientPortionForm()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        if (!$insuranceCompanyId) {
            return redirect()->route('payments.index')->with('error', 'No insurance company associated with your account.');
        }
        return view('payments.record-client-portion');
    }

    /**
     * Store a recorded client-portion payment. Uses same logic as API so the record appears in all 4 places.
     */
    public function storeRecordClientPortion(Request $request)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        if (!$insuranceCompanyId) {
            return redirect()->route('payments.index')->with('error', 'No insurance company associated with your account.');
        }

        $request->validate([
            'policy_number' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'mobile_money_number' => ['nullable', 'string', 'max:255'],
        ], [
            'policy_number.required' => 'Policy number is required.',
            'amount.required' => 'Amount collected is required.',
            'amount.min' => 'Amount must be at least 0.01.',
        ]);

        $paymentReference = 'MANUAL-CP-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

        $validated = [
            'insurance_company_id' => $insuranceCompanyId,
            'policy_number' => trim($request->input('policy_number')),
            'amount' => (float) $request->input('amount'),
            'payment_reference' => $paymentReference,
            'payment_method' => 'mobile_money',
            'mobile_money_number' => $request->input('mobile_money_number') ?: null,
            'payment_notes' => 'Client portion recorded manually (collected e.g. via mobile money).',
            'source' => 'kashtre',
            'processed_by' => auth()->id(),
        ];

        $result = RecordClientPortionService::record($validated);

        if (!$result['success']) {
            return redirect()->route('payments.record-client-portion')
                ->withInput()
                ->with('error', $result['message'] ?? 'Could not record payment.');
        }

        $clientId = $result['client_id'];
        return redirect()
            ->route('clients.account-statement', $clientId)
            ->with('success', 'Payment of UGX ' . number_format($validated['amount'], 2) . ' recorded. It appears in Payments, this client’s account statement, Balance statement, and Third-party vendor page.');
    }
}
