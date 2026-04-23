<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\KashtreApiService;
use Illuminate\Http\Request;
use App\Support\PaymentReference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected $kashtreApi;

    public function __construct(KashtreApiService $kashtreApi)
    {
        $this->kashtreApi = $kashtreApi;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        if (!$insuranceCompanyId) {
            return redirect()->route('dashboard')
                ->with('error', 'No insurance company associated with your account.');
        }

        // Fetch invoices from Kashtre API
        $result = $this->kashtreApi->getInvoicesForInsuranceCompany($insuranceCompanyId);
        
        // Log for debugging
        Log::info('InvoiceController@index - API Response', [
            'insurance_company_id' => $insuranceCompanyId,
            'success' => $result['success'] ?? false,
            'data_count' => is_array($result['data'] ?? null) ? count($result['data']) : 0,
            'message' => $result['message'] ?? null,
            'result_keys' => array_keys($result ?? []),
        ]);
        
        // Check if the API call was successful
        if (isset($result['success']) && !$result['success']) {
            return view('invoices.index', [
                'invoices' => collect([]),
                'error' => $result['message'] ?? 'Failed to fetch invoices from Kashtre. Please check the logs for more details.',
                'debug' => $result['debug'] ?? null,
            ]);
        }
        
        // Extract invoices from the response
        // The API returns: { "success": true, "data": [...] }
        $invoicesData = $result['data'] ?? [];
        
        // Ensure it's an array
        if (!is_array($invoicesData)) {
            Log::warning('InvoiceController@index - Invalid data format', [
                'insurance_company_id' => $insuranceCompanyId,
                'data_type' => gettype($invoicesData),
                'data' => $invoicesData,
            ]);
            $invoicesData = [];
        }

        // Also include locally synced invoices from Kashtre (that were recorded via API)
        $localInvoices = Invoice::all()->map(function ($inv) {
            // Extract Kashtre client name from notes if available
            $kashtreName = 'Kashtre Sync';
            if ($inv->notes && preg_match('/Client:\s*([^,]+)/', $inv->notes, $matches)) {
                $kashtreName = trim($matches[1]);
            }
            
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'total_amount' => $inv->total_amount,
                'paid_amount' => $inv->paid_amount ?? 0,
                'balance_amount' => $inv->balance_amount ?? 0,
                'balance_due' => $inv->balance_amount ?? 0,
                'status' => $inv->status,
                'payment_status' => $inv->status,
                'invoice_date' => $inv->invoice_date,
                'created_at' => $inv->created_at,
                'client_name' => $kashtreName,
                'client_id' => null,
                'client_phone' => null,
                'business_name' => null,
                'branch_name' => null,
                'notes' => $inv->notes ?? null,
                'source' => 'local_sync',
            ];
        })->toArray();

        // Merge API invoices with local synced invoices
        $allInvoices = array_merge($invoicesData, $localInvoices);
        $invoices = collect($allInvoices);
            
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('invoices.create');
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
    public function show($invoiceId)
    {
        // Fetch invoice details from Kashtre API
        $result = $this->kashtreApi->getInvoiceDetails($invoiceId);
        
        if (!$result['success'] || !$result['data']) {
            return redirect()->route('invoices.index')
                ->with('error', $result['message'] ?? 'Failed to fetch invoice details.');
        }

        $invoiceData = $result['data'];
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        $invoicePayments = $insuranceCompanyId
            ? $this->paymentsForKashtreInvoice($invoiceId, $insuranceCompanyId)
            : collect();

        return view('invoices.show', compact('invoiceData', 'invoiceId', 'invoicePayments'));
    }

    /**
     * Payments recorded in this app for a Kashtre invoice (client portion from Kashtre + insurer payments).
     */
    private function paymentsForKashtreInvoice($kashtreInvoiceId, int $insuranceCompanyId)
    {
        $idStr = (string) $kashtreInvoiceId;

        return Payment::query()
            ->with(['client', 'policy', 'processedBy'])
            ->where(function ($q) use ($kashtreInvoiceId, $idStr) {
                $q->where('payment_metadata->kashtre_invoice_id', $idStr)
                    ->orWhere('payment_metadata->kashtre_invoice_id', (int) $kashtreInvoiceId);
            })
            ->where(function ($q) use ($insuranceCompanyId) {
                $q->where('payment_metadata->insurance_company_id', $insuranceCompanyId)
                    ->orWhere('payment_metadata->insurance_company_id', (string) $insuranceCompanyId)
                    ->orWhereRelation('policy', 'insurance_company_id', $insuranceCompanyId);
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Mark invoice as paid/cleared
     */
    public function markAsPaid(Request $request, $invoiceId)
    {
        try {
            Log::info('=== markAsPaid METHOD CALLED ===', [
                'invoice_id' => $invoiceId,
                'user_id' => auth()->id(),
                'request_method' => $request->method(),
                'request_data' => $request->all(),
            ]);
        
            try {
            $validated = $request->validate([
                'payment_method' => 'required|in:bank_transfer,mobile_money,cash',
                'payment_reference' => 'nullable|string|max:255',
                'payment_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'phone_number' => 'required_if:payment_method,mobile_money|nullable|string|max:20',
                'proof_of_payment' => 'required_if:payment_method,bank_transfer,cash|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
                'amount' => 'required|numeric|min:0',
            ]);
            
            Log::info('Validation passed', ['validated' => $validated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected exception during validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Validation error: ' . $e->getMessage());
        }

        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        Log::info('Insurance company check', ['insurance_company_id' => $insuranceCompanyId]);
        
        if (!$insuranceCompanyId) {
            Log::error('No insurance company ID found');
            return back()->with('error', 'No insurance company associated with your account.');
        }

        // Get invoice details first
        Log::info('Fetching invoice details', ['invoice_id' => $invoiceId]);
        $invoiceDetails = $this->kashtreApi->getInvoiceDetails($invoiceId);
        
        Log::info('Invoice details response', [
            'success' => $invoiceDetails['success'] ?? false,
            'has_data' => isset($invoiceDetails['data']),
        ]);
        
        if (!$invoiceDetails['success'] || !$invoiceDetails['data']) {
            Log::error('Failed to fetch invoice details', ['response' => $invoiceDetails]);
            return back()->with('error', 'Failed to fetch invoice details.');
        }
        
        // Create Payment record FIRST with pending status (before processing payment)
        Log::info('About to create payment record', [
            'invoice_id' => $invoiceId,
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
        ]);
        
        try {
            $payment = $this->createPaymentRecord($invoiceId, $invoiceDetails, $validated, 'pending', null);
            Log::info('Payment record created successfully, continuing with payment processing', [
                'payment_id' => $payment->id ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a policy_id NOT NULL constraint error
            if (isset($e->errorInfo[2]) && strpos($e->errorInfo[2], 'policy_id') !== false && strpos($e->errorInfo[2], 'NULL') !== false) {
                return back()->with('error', 'Payment creation failed: policy_id cannot be null. Please run the migration: php artisan migrate');
            }
            
            return back()->with('error', 'Failed to create payment record: ' . ($e->errorInfo[2] ?? $e->getMessage()) . '. Please check the logs for details.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create payment record: ' . $e->getMessage() . '. Please check the logs for details.');
        }
        
        if (!$payment) {
            Log::error('CRITICAL: Failed to create payment record!', [
                'invoice_id' => $invoiceId,
                'user_id' => auth()->id(),
                'insurance_company_id' => $insuranceCompanyId,
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'message' => 'Payment record creation failed. Check logs for details.',
            ]);
            
            // FAIL the request if payment creation fails - we need to track all payments
            return back()->with('error', 'Failed to create payment record. Please check the logs and try again. Error details have been logged.');
        }
        
        Log::info('Payment record created successfully', [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->payment_reference,
            'invoice_id' => $invoiceId,
        ]);
        
        // Handle proof of payment upload for bank transfer and cash
        $proofOfPaymentPath = null;
        if (in_array($validated['payment_method'], ['bank_transfer', 'cash']) && $request->hasFile('proof_of_payment')) {
            $file = $request->file('proof_of_payment');
            $fileName = 'proof_' . $invoiceId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $proofOfPaymentPath = $file->storeAs('proof-of-payments', $fileName, 'public');
            
            Log::info('Proof of payment uploaded', [
                'invoice_id' => $invoiceId,
                'file_path' => $proofOfPaymentPath,
                'file_name' => $fileName,
                'payment_method' => $validated['payment_method'],
            ]);
            
            // Update payment record with proof of payment path
            if ($payment) {
                $payment->update([
                    'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                        'proof_of_payment_path' => $proofOfPaymentPath,
                    ]),
                ]);
            }
        }
        
        // Handle mobile money payment via Yo Payments
        if ($validated['payment_method'] === 'mobile_money') {
            try {
                $phone = $validated['phone_number'];
                
                // Format phone number: remove + if present, ensure 256XXXXXXXXX format
                if (str_starts_with($phone, '+')) {
                    $phone = substr($phone, 1);
                } elseif (str_starts_with($phone, '0')) {
                    $phone = '256' . substr($phone, 1);
                }
                
                // Initialize YoAPI
                $yoPayments = new \App\Payments\YoAPI(
                    config('payments.yo_username'),
                    config('payments.yo_password')
                );
                
                $yoPayments->set_instant_notification_url(config('payments.webhook_url'));
                $externalReference = PaymentReference::forInvoiceYoExternal();
                $yoPayments->set_external_reference($externalReference);
                
                // Create description
                $description = 'Payment for Invoice #' . ($invoiceDetails['data']['invoice']['invoice_number'] ?? $invoiceId);
                if (strlen($description) > 160) {
                    $description = substr($description, 0, 157) . '...';
                }
                
                Log::info('Initiating mobile money payment for invoice', [
                    'invoice_id' => $invoiceId,
                    'phone' => $phone,
                    'amount' => $validated['amount'],
                    'description' => $description,
                    'external_reference' => $externalReference,
                ]);
                
                // Process payment through YoAPI
                $yoResult = $yoPayments->ac_deposit_funds($phone, $validated['amount'], $description);
                
                Log::info('YoAPI response for invoice payment', [
                    'invoice_id' => $invoiceId,
                    'result' => $yoResult,
                ]);
                
                // Check if payment request was initiated successfully
                if (isset($yoResult['Status']) && $yoResult['Status'] === 'OK' && isset($yoResult['TransactionReference'])) {
                    // Payment request sent successfully - update payment record with transaction reference
                    $transactionReference = $yoResult['TransactionReference'];
                    
                    if ($payment) {
                        $payment->update([
                            'payment_reference' => $transactionReference,
                            'transaction_id' => $transactionReference,
                            'payment_notes' => ($validated['notes'] ?? '') . ' | Mobile Money Transaction Reference: ' . $transactionReference,
                            'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                'yo_transaction_reference' => $transactionReference,
                                'yo_status' => $yoResult['Status'] ?? null,
                                'yo_status_message' => $yoResult['StatusMessage'] ?? null,
                            ]),
                        ]);
                        
                        Log::info('Payment record updated with transaction reference', [
                            'payment_id' => $payment->id,
                            'transaction_reference' => $transactionReference,
                        ]);
                    }
                    
                    // Add transaction reference to notes for Kashtre
                    $validated['notes'] = ($validated['notes'] ?? '') . ' | Mobile Money Transaction Reference: ' . $transactionReference;
                    $validated['payment_reference'] = $transactionReference;
                    
                    // Continue to mark as paid in Kashtre (payment is pending)
                    $result = $this->kashtreApi->markInvoiceAsPaid($invoiceId, $insuranceCompanyId, $validated);
                    
                    if ($result['success']) {
                        $this->applyPolicyBenefitUsageOnSuccessfulPayment($invoiceId, $insuranceCompanyId);
                        return redirect()->route('invoices.show', $invoiceId)
                            ->with('success', 'Mobile money payment request sent. Please wait for confirmation from the customer.');
                    }
                } else {
                    // Payment request failed
                    $errorMessage = $yoResult['StatusMessage'] ?? $yoResult['ErrorMessage'] ?? 'Failed to initiate mobile money payment';
                    Log::error('Mobile money payment failed', [
                        'invoice_id' => $invoiceId,
                        'yo_result' => $yoResult,
                    ]);
                    
                    return back()->with('error', 'Failed to initiate mobile money payment: ' . $errorMessage);
                }
            } catch (\Exception $e) {
                Log::error('Exception during mobile money payment', [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return back()->with('error', 'Error processing mobile money payment: ' . $e->getMessage());
            }
        } else {
            // Bank transfer or cash - upload proof of payment and mark as pending review
            $validated['proof_of_payment_path'] = $proofOfPaymentPath;
            $validated['payment_status'] = 'pending_review'; // Status for review
            
            // Update payment record with payment reference if provided
            if ($payment && $validated['payment_reference']) {
                $payment->update([
                    'payment_reference' => $validated['payment_reference'],
                    'transaction_id' => $validated['payment_reference'],
                ]);
            }
            
            Log::info('Calling Kashtre API to mark invoice as paid', [
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'payment_method' => $validated['payment_method'],
            ]);
            
            $result = $this->kashtreApi->markInvoiceAsPaid($invoiceId, $insuranceCompanyId, $validated);
            
            Log::info('Kashtre API response', [
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'No message',
                'result' => $result,
            ]);

            if ($result['success']) {
                $this->applyPolicyBenefitUsageOnSuccessfulPayment($invoiceId, $insuranceCompanyId);
                Log::info('Payment processed successfully - redirecting to invoice show page');
                return redirect()->route('invoices.show', $invoiceId)
                    ->with('success', 'Proof of payment uploaded successfully. Payment is pending review and will be marked as paid once approved.');
            } else {
                Log::error('Kashtre API returned failure', ['result' => $result]);
                return back()->with('error', $result['message'] ?? 'Failed to mark invoice as paid in Kashtre.');
            }
        }

            Log::error('Unexpected end of markAsPaid method - no return statement reached', [
                'invoice_id' => $invoiceId,
                'payment_method' => $validated['payment_method'] ?? 'unknown',
            ]);
            return back()->with('error', 'An unexpected error occurred. Please check the logs.');
        } catch (\Exception $e) {
            // Catch any unhandled exceptions
            Log::error('UNHANDLED EXCEPTION in markAsPaid', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An error occurred: ' . $e->getMessage() . '. Please check the logs for details.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }

    /**
     * Generate PDF for the invoice.
     */
    public function generatePdf(Invoice $invoice)
    {
        // TODO: Implement PDF generation
        return response()->json(['message' => 'PDF generation not yet implemented']);
    }

    /**
     * Reduce policy benefit usage only after insurer payment is successfully marked as paid.
     */
    private function applyPolicyBenefitUsageOnSuccessfulPayment(string $invoiceId, int $insuranceCompanyId): void
    {
        try {
            DB::transaction(function () use ($invoiceId, $insuranceCompanyId) {
                $insuranceAuth = \App\Models\InsuranceAuthorization::where('insurance_company_id', $insuranceCompanyId)
                    ->where('kashtre_invoice_id', (string) $invoiceId)
                    ->where('status', 'completed')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (!$insuranceAuth) {
                    Log::info('InvoiceController: No completed insurance authorization found for benefit reduction', [
                        'invoice_id' => $invoiceId,
                        'insurance_company_id' => $insuranceCompanyId,
                    ]);
                    return;
                }

                $metadata = $insuranceAuth->metadata ?? [];
                if (!empty($metadata['benefit_usage_applied_at'])) {
                    Log::info('InvoiceController: Benefit usage already applied, skipping', [
                        'insurance_authorization_id' => $insuranceAuth->id,
                        'invoice_id' => $invoiceId,
                    ]);
                    return;
                }

                $policyBenefitId = $metadata['policy_benefit_id'] ?? null;
                $insuranceTotal = (float) ($insuranceAuth->insurance_total ?? 0);
                if (!$policyBenefitId || $insuranceTotal <= 0) {
                    $metadata['benefit_usage_applied_at'] = now()->toDateTimeString();
                    $metadata['benefit_usage_applied_amount'] = 0;
                    $metadata['benefit_usage_note'] = 'No applicable benefit usage to apply on payment.';
                    $insuranceAuth->update(['metadata' => $metadata]);
                    return;
                }

                $policyBenefit = \App\Models\PolicyBenefit::lockForUpdate()->find($policyBenefitId);
                if (!$policyBenefit) {
                    Log::warning('InvoiceController: Policy benefit not found while applying usage', [
                        'invoice_id' => $invoiceId,
                        'insurance_authorization_id' => $insuranceAuth->id,
                        'policy_benefit_id' => $policyBenefitId,
                    ]);
                    return;
                }

                $policyBenefit->used_amount = (float) $policyBenefit->used_amount + $insuranceTotal;
                $policyBenefit->updateRemainingAmount();

                $metadata['benefit_usage_applied_at'] = now()->toDateTimeString();
                $metadata['benefit_usage_applied_amount'] = $insuranceTotal;
                $insuranceAuth->update(['metadata' => $metadata]);

                Log::info('InvoiceController: Policy benefit usage applied after successful payment', [
                    'invoice_id' => $invoiceId,
                    'insurance_authorization_id' => $insuranceAuth->id,
                    'policy_benefit_id' => $policyBenefit->id,
                    'applied_amount' => $insuranceTotal,
                    'used_amount' => $policyBenefit->used_amount,
                    'remaining_amount' => $policyBenefit->remaining_amount,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('InvoiceController: Failed to apply policy benefit usage after successful payment', [
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a Payment record when invoice is marked as paid
     */
    private function createPaymentRecord($invoiceId, $invoiceDetails, $validated, $status, $transactionReference = null)
    {
        try {
            // Log attempt to create payment
            Log::info('=== STARTING PAYMENT RECORD CREATION ===', [
                'invoice_id' => $invoiceId,
                'user_id' => auth()->id(),
                'insurance_company_id' => auth()->user()->insurance_company_id,
                'payment_method' => $validated['payment_method'] ?? null,
                'amount' => $validated['amount'] ?? null,
            ]);
            $invoice = $invoiceDetails['data']['invoice'] ?? [];
            $client = $invoice['client'] ?? [];
            
            // Get client_id from invoice data or find by client name/phone
            $clientId = null;
            
            // Try to find client by phone number (cell_phone column in clients table)
            $phoneNumber = $client['phone_number'] ?? $invoice['client_phone'] ?? null;
            if ($phoneNumber) {
                // Remove + if present and normalize
                $phoneNumber = ltrim($phoneNumber, '+');
                $clientModel = \App\Models\Client::where('cell_phone', $phoneNumber)
                    ->orWhere('cell_phone', '+' . $phoneNumber)
                    ->first();
                if ($clientModel) {
                    $clientId = $clientModel->id;
                }
            }
            
            // If not found by phone, try to find by name (split full name into parts)
            if (!$clientId && isset($invoice['client_name'])) {
                $clientName = trim($invoice['client_name']);
                if ($clientName) {
                    // Try to match by first_name and surname
                    $nameParts = explode(' ', $clientName, 3);
                    $firstName = $nameParts[0] ?? null;
                    $surname = $nameParts[1] ?? null;
                    $otherNames = $nameParts[2] ?? null;
                    
                    if ($firstName && $surname) {
                        $clientModel = \App\Models\Client::where('first_name', 'like', $firstName . '%')
                            ->where('surname', 'like', $surname . '%')
                            ->first();
                        if ($clientModel) {
                            $clientId = $clientModel->id;
                        }
                    }
                }
            }
            
            if (!$clientId) {
                Log::info('Client not found in third-party system', [
                    'invoice_id' => $invoiceId,
                    'client_name' => $invoice['client_name'] ?? null,
                    'client_phone' => $invoice['client_phone'] ?? null,
                ]);
            }
            
            // Get policy_id - try to find policy by client
            $policyId = null;
            if ($clientId) {
                $policy = \App\Models\Policy::where('principal_member_id', $clientId)
                    ->where('insurance_company_id', auth()->user()->insurance_company_id)
                    ->where('status', 'active')
                    ->latest()
                    ->first();
                $policyId = $policy->id ?? null;
            }
            
            // Generate payment reference - for mobile money, it will be updated with transaction reference
            // For bank/cash, use provided reference or generate one
            $paymentReference = null;
            if ($validated['payment_method'] === 'mobile_money') {
                // Mobile money reference will be replaced by Yo TransactionReference when successful
                $paymentReference = PaymentReference::forMobilePending();
            } else {
                // Bank transfer or cash - use provided reference or generate one
                $paymentReference = $validated['payment_reference'] ?? PaymentReference::forBankOrCashDefault();
            }
            
            // Payment status is always pending initially
            // Will be updated to 'completed' when payment is confirmed/reviewed
            $paymentStatus = 'pending';
            
            // Note: policy_id can be null now (after migration), so we can create payments without a policy
            // This is useful for payments from Kashtre invoices where the client/policy might not exist in third-party system
            if (!$policyId) {
                Log::info('No policy found for payment, creating payment without policy_id', [
                    'invoice_id' => $invoiceId,
                    'client_id' => $clientId,
                    'insurance_company_id' => auth()->user()->insurance_company_id,
                ]);
            }
            
            // Log the data we're about to insert for debugging
            $paymentData = [
                'payment_reference' => $paymentReference,
                'invoice_id' => null, // Invoice is in Kashtre, not in third-party system
                'policy_id' => $policyId, // Can be null now
                'client_id' => $clientId,
                'payment_type' => 'full_payment', // Changed from 'invoice_payment' to valid enum value
                'amount' => $validated['amount'],
                'paid_amount' => $validated['amount'],
                'balance_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'mobile_money_number' => $validated['phone_number'] ?? null,
                'transaction_id' => $transactionReference,
                'status' => $paymentStatus,
                'payment_date' => $validated['payment_date'] ?? now(),
                'received_date' => now(),
                'payment_notes' => $validated['notes'] ?? 'Payment for invoice from Kashtre',
                'payment_metadata' => [
                    'kashtre_invoice_id' => $invoiceId,
                    'invoice_number' => $invoice['invoice_number'] ?? null,
                    'client_name' => $invoice['client_name'] ?? null,
                    'client_phone' => $invoice['client_phone'] ?? null,
                    'proof_of_payment_path' => $validated['proof_of_payment_path'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'insurance_company_id' => auth()->user()->insurance_company_id, // Store insurance company ID for filtering
                ],
                'processed_by' => auth()->id(),
            ];
            
            Log::info('Attempting to create payment record', [
                'invoice_id' => $invoiceId,
                'payment_data' => $paymentData,
                'user_id' => auth()->id(),
                'insurance_company_id' => auth()->user()->insurance_company_id,
            ]);
            
            // Check if payment_reference already exists
            $existingPayment = Payment::where('payment_reference', $paymentReference)->first();
            if ($existingPayment) {
                Log::warning('Payment reference already exists, generating new one', [
                    'existing_reference' => $paymentReference,
                    'existing_payment_id' => $existingPayment->id,
                ]);
                // Generate a new unique reference (no time() suffix — avoids long strings)
                $paymentReference = PaymentReference::forBankOrCashDefault();
                $paymentData['payment_reference'] = $paymentReference;
                Log::info('Generated new payment reference', [
                    'new_reference' => $paymentReference,
                ]);
            }
            
            // Create payment record
            $payment = Payment::create($paymentData);
            
            Log::info('Payment record created', [
                'payment_id' => $payment->id,
                'payment_reference' => $paymentReference,
                'kashtre_invoice_id' => $invoiceId,
                'invoice_number' => $invoice['invoice_number'] ?? null,
                'client_id' => $clientId,
                'policy_id' => $policyId,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'status' => $paymentStatus,
            ]);
            
            return $payment;
        } catch (\Illuminate\Database\QueryException $e) {
            // Database constraint errors
            $errorInfo = [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'sql_state' => $e->errorInfo[0] ?? null,
                'sql_error_code' => $e->errorInfo[1] ?? null,
                'sql_error_message' => $e->errorInfo[2] ?? null,
                'trace' => $e->getTraceAsString(),
            ];
            
            // Check if it's a NOT NULL constraint error for policy_id
            if (isset($e->errorInfo[2]) && strpos($e->errorInfo[2], 'policy_id') !== false && strpos($e->errorInfo[2], 'NULL') !== false) {
                $errorInfo['migration_required'] = true;
                $errorInfo['migration_file'] = '2026_02_16_000001_make_policy_id_nullable_in_payments_table.php';
                $errorInfo['action'] = 'Run: php artisan migrate';
            }
            
            Log::error('Database error creating payment record', $errorInfo);
            
            // Re-throw so the calling method can handle it
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create payment record', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw so the calling method can handle it
            throw $e;
        }
    }

    /**
     * Process bulk payment for multiple invoices
     */
    public function bulkPay(Request $request)
    {
        try {
            Log::info('=== bulkPay METHOD CALLED ===', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ]);

            $validated = $request->validate([
                'invoice_ids' => 'required|string',
                'payment_method' => 'required|in:bank_transfer,mobile_money,cash',
                'payment_reference' => 'nullable|string|max:255',
                'payment_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'phone_number' => 'required_if:payment_method,mobile_money|nullable|string|max:20',
                'proof_of_payment' => 'required_if:payment_method,bank_transfer,cash|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
                'total_amount' => 'required|numeric|min:0',
            ]);

            $insuranceCompanyId = auth()->user()->insurance_company_id;
            
            if (!$insuranceCompanyId) {
                return back()->with('error', 'No insurance company associated with your account.');
            }

            // Parse invoice IDs
            $invoiceIds = array_filter(explode(',', $validated['invoice_ids']));
            
            if (empty($invoiceIds)) {
                return back()->with('error', 'No invoices selected for payment.');
            }

            Log::info('Processing bulk payment', [
                'invoice_count' => count($invoiceIds),
                'total_amount' => $validated['total_amount'],
                'payment_method' => $validated['payment_method'],
            ]);

            $successCount = 0;
            $failedInvoices = [];
            $proofOfPaymentPath = null;

            // Handle proof of payment upload
            if ($request->hasFile('proof_of_payment')) {
                $file = $request->file('proof_of_payment');
                $proofOfPaymentPath = $file->store('proofs-of-payment', 'public');
            }

            // Generate payment reference for mobile money if not provided
            if ($validated['payment_method'] === 'mobile_money' && empty($validated['payment_reference'])) {
                $validated['payment_reference'] = PaymentReference::forBulkMobileMoney();
            }

            // Process each invoice
            foreach ($invoiceIds as $invoiceId) {
                try {
                    // Get invoice details
                    $invoiceDetails = $this->kashtreApi->getInvoiceDetails($invoiceId);
                    
                    if (!$invoiceDetails['success'] || !$invoiceDetails['data']) {
                        $failedInvoices[] = ['id' => $invoiceId, 'reason' => 'Failed to fetch invoice details'];
                        continue;
                    }

                    $invoice = $invoiceDetails['data']['invoice'] ?? [];
                    // Ensure numeric balance due
                    $balanceDueRaw = $invoice['balance_due'] ?? $invoice['total_amount'] ?? 0;
                    $balanceDue = (float) $balanceDueRaw;

                    // If there is nothing outstanding on this invoice, skip mobile money
                    if ($balanceDue <= 0) {
                        Log::warning('Bulk payment: invoice has no outstanding balance, skipping mobile money', [
                            'invoice_id' => $invoiceId,
                            'balance_due_raw' => $balanceDueRaw,
                            'balance_due' => $balanceDue,
                        ]);
                        $failedInvoices[] = [
                            'id' => $invoiceId,
                            'reason' => 'Invoice has no outstanding balance to pay.',
                        ];
                        continue;
                    }

                    // Create payment record for this invoice
                    $paymentData = $validated;
                    $paymentData['amount'] = $balanceDue;
                    $paymentData['proof_of_payment_path'] = $proofOfPaymentPath;

                    $payment = $this->createPaymentRecord($invoiceId, $invoiceDetails, $paymentData, 'pending', null);
                    
                    // Process payment if mobile money
                    if ($validated['payment_method'] === 'mobile_money' && !empty($validated['phone_number'])) {
                        try {
                            // Format phone number similar to single-invoice flow
                            $phone = $validated['phone_number'];
                            $phone = preg_replace('/\s+/', '', $phone);
                            if (str_starts_with($phone, '+')) {
                                $phone = substr($phone, 1);
                            } elseif (str_starts_with($phone, '0')) {
                                $phone = '256' . substr($phone, 1);
                            }

                            // Initialize YoAPI with credentials
                            $yoApi = new \App\Payments\YoAPI(
                                config('payments.yo_username'),
                                config('payments.yo_password')
                            );
                            $yoApi->set_instant_notification_url(config('payments.webhook_url'));
                            $yoApi->set_external_reference($validated['payment_reference']);

                            // Build narrative/description
                            $description = 'Bulk payment for Invoice #' . ($invoice['invoice_number'] ?? $invoiceId);
                            if (strlen($description) > 160) {
                                $description = substr($description, 0, 157) . '...';
                            }

                            Log::info('Initiating mobile money bulk payment for invoice', [
                                'invoice_id' => $invoiceId,
                                'phone' => $phone,
                                'amount' => $balanceDue,
                                'description' => $description,
                                'payment_reference' => $validated['payment_reference'],
                            ]);

                            // Send mobile money request
                            $yoResult = $yoApi->ac_deposit_funds($phone, $balanceDue, $description);

                            Log::info('YoAPI response for bulk invoice payment', [
                                'invoice_id' => $invoiceId,
                                'result' => $yoResult,
                            ]);

                            if (isset($yoResult['Status']) && $yoResult['Status'] === 'OK' && isset($yoResult['TransactionReference'])) {
                                $transactionReference = $yoResult['TransactionReference'];

                                // Update payment with transaction reference and Yo metadata (keep status pending; cron will confirm)
                                if ($payment) {
                                    $payment->update([
                                        'payment_reference' => $transactionReference,
                                        'transaction_id' => $transactionReference,
                                        'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                                            'yo_transaction_reference' => $transactionReference,
                                            'yo_status' => $yoResult['Status'] ?? null,
                                            'yo_status_message' => $yoResult['StatusMessage'] ?? null,
                                        ]),
                                    ]);
                                }

                                // Prepare payload for Kashtre mark-as-paid (mobile money pending)
                                $kashtrePayload = $paymentData;
                                $kashtrePayload['amount'] = $balanceDue;
                                $kashtrePayload['payment_reference'] = $transactionReference;
                                $kashtrePayload['transaction_id'] = $transactionReference;
                                $kashtrePayload['payment_date'] = $validated['payment_date'] ?? now()->toDateString();
                                $kashtrePayload['notes'] = ($kashtrePayload['notes'] ?? '') . ' | Mobile Money Transaction Reference: ' . $transactionReference;

                                $markPaidResult = $this->kashtreApi->markInvoiceAsPaid(
                                    $invoiceId,
                                    $insuranceCompanyId,
                                    $kashtrePayload
                                );

                                if ($markPaidResult['success']) {
                                    $successCount++;
                                } else {
                                    $failedInvoices[] = [
                                        'id' => $invoiceId,
                                        'reason' => 'Failed to mark invoice as paid in Kashtre',
                                    ];
                                }
                            } else {
                                $errorMessage = $yoResult['StatusMessage'] ?? $yoResult['ErrorMessage'] ?? 'Unknown error';
                                $failedInvoices[] = [
                                    'id' => $invoiceId,
                                    'reason' => 'Mobile money payment failed: ' . $errorMessage,
                                ];
                            }
                        } catch (\Exception $e) {
                            Log::error('Error during mobile money bulk payment', [
                                'invoice_id' => $invoiceId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $failedInvoices[] = [
                                'id' => $invoiceId,
                                'reason' => 'Mobile money exception: ' . $e->getMessage(),
                            ];
                        }
                    } else {
                        // For bank transfer and cash, just create the payment record
                        // Invoice will be marked as paid after review
                        $successCount++;
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process invoice in bulk payment', [
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage(),
                    ]);
                    $failedInvoices[] = ['id' => $invoiceId, 'reason' => $e->getMessage()];
                }
            }

            if ($successCount > 0) {
                $message = "Successfully processed payment for {$successCount} invoice(s).";
                if (!empty($failedInvoices)) {
                    $message .= " Failed: " . count($failedInvoices) . " invoice(s).";
                }
                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Failed to process any invoices. Please check the logs for details.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Bulk payment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An error occurred while processing bulk payment: ' . $e->getMessage());
        }
    }
}
