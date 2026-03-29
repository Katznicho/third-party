<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Policy;
use App\Services\KashtreApiService;
use App\Services\PaymentCompletionService;
use App\Services\RecordClientPortionService;
use App\Support\PaymentReference;
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
        
        // All payments for this insurer: staff’s own entries, any payment tied to a policy here,
        // or Kashtre client-portion rows (metadata.insurance_company_id — int/string safe).
        $payments = Payment::with(['invoice', 'policy', 'client'])
            ->where(function ($query) use ($insuranceCompanyId, $userId) {
                $query->where('processed_by', $userId)
                    ->orWhereRelation('policy', 'insurance_company_id', $insuranceCompanyId)
                    ->orWhere(function ($q) use ($insuranceCompanyId) {
                        $q->where('payment_metadata->insurance_company_id', $insuranceCompanyId)
                            ->orWhere('payment_metadata->insurance_company_id', (string) $insuranceCompanyId);
                    });
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
     * Step 1 for Bank/Cash: store TID on a pending premium payment (no status change).
     */
    public function storeTid(Request $request, Payment $payment)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;

        if ($payment->payment_type !== 'premium_payment') {
            return redirect()->back()->with('error', 'This action only applies to premium payments.');
        }
        if ($payment->status !== 'pending') {
            return redirect()->back()->with('error', 'This payment is not pending.');
        }

        $request->validate([
            'transaction_id' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'transaction_id.required' => 'Please enter the bank TID / reference number.',
        ]);

        $payment->load('policy');
        if (!$payment->policy || $payment->policy->insurance_company_id != $insuranceCompanyId) {
            abort(403, 'You do not have access to this payment.');
        }

        $existingNotes = $payment->payment_notes ? trim($payment->payment_notes) . "\n" : '';
        $notePrefix = 'TID recorded: ' . $request->input('transaction_id');
        $reason = trim((string) $request->input('reason'));
        $newNotes = $existingNotes . $notePrefix . ($reason ? ' (' . $reason . ')' : '');

        $payment->update([
            'transaction_id' => $request->input('transaction_id'),
            'payment_notes' => $newNotes,
        ]);

        return redirect()->route('clients.show', $payment->client_id)
            ->with('success', 'TID recorded. You can now verify and mark this payment as paid.');
    }

    /**
     * Step 2 for Bank/Cash: verify and mark a pending premium payment as received.
     * Updates payment to completed and activates the policy.
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
            'reason.required' => 'Please provide a reason for approving this payment.',
        ]);

        $payment->load('policy');
        if (!$payment->policy || $payment->policy->insurance_company_id != $insuranceCompanyId) {
            abort(403, 'You do not have access to this payment.');
        }

        $reason = trim($request->input('reason'));
        $existingNotes = $payment->payment_notes ? trim($payment->payment_notes) . "\n" : '';
        $newNotes = $existingNotes . 'Verified & marked as received: ' . $reason;

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
            ->with('success', 'Payment verified and marked as received. Policy is now active.');
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

        $paymentReference = PaymentReference::forClientPortionManual();

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

        // Notify Kashtre (external) so it can update its 2 sections: payments + client account statement
        $kashtrePayload = [
            'insurance_company_id' => $insuranceCompanyId,
            'policy_number' => $validated['policy_number'],
            'amount' => $validated['amount'],
            'payment_reference' => $validated['payment_reference'],
            'client_id' => $result['client_id'],
            'mobile_money_number' => $validated['mobile_money_number'] ?? null,
            'payment_date' => now()->format('Y-m-d'),
        ];
        $notify = app(KashtreApiService::class)->notifyClientPortionRecorded($kashtrePayload);
        if (!$notify['success']) {
            \Illuminate\Support\Facades\Log::warning('Kashtre was not notified of client-portion payment', $notify);
        }

        $clientId = $result['client_id'];
        return redirect()
            ->route('clients.account-statement', $clientId)
            ->with('success', 'Payment of UGX ' . number_format($validated['amount'], 2) . ' recorded. It appears in Payments, this client’s account statement, Balance statement, and Third-party vendor page.');
    }
}
