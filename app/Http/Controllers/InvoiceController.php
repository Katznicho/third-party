<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\KashtreApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        
        $invoices = collect($invoicesData);
            
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
        
        return view('invoices.show', compact('invoiceData', 'invoiceId'));
    }

    /**
     * Mark invoice as paid/cleared
     */
    public function markAsPaid(Request $request, $invoiceId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,mobile_money,cash',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'phone_number' => 'required_if:payment_method,mobile_money|string|max:20',
            'proof_of_payment' => 'required_if:payment_method,bank_transfer,cash|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
            'amount' => 'required|numeric|min:0',
        ]);

        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        if (!$insuranceCompanyId) {
            return back()->with('error', 'No insurance company associated with your account.');
        }

        // Get invoice details first
        $invoiceDetails = $this->kashtreApi->getInvoiceDetails($invoiceId);
        if (!$invoiceDetails['success'] || !$invoiceDetails['data']) {
            return back()->with('error', 'Failed to fetch invoice details.');
        }
        
        // Create Payment record FIRST with pending status (before processing payment)
        $payment = $this->createPaymentRecord($invoiceId, $invoiceDetails, $validated, 'pending', null);
        
        if (!$payment) {
            Log::warning('Failed to create payment record, but continuing with payment processing', [
                'invoice_id' => $invoiceId,
            ]);
        }
        
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
                $externalReference = 'INV-' . $invoiceId . '-' . time() . '-' . uniqid();
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
            
            $result = $this->kashtreApi->markInvoiceAsPaid($invoiceId, $insuranceCompanyId, $validated);

            if ($result['success']) {
                return redirect()->route('invoices.show', $invoiceId)
                    ->with('success', 'Proof of payment uploaded successfully. Payment is pending review and will be marked as paid once approved.');
            }
        }

        return back()->with('error', $result['message'] ?? 'Failed to clear payment.');
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
     * Create a Payment record when invoice is marked as paid
     */
    private function createPaymentRecord($invoiceId, $invoiceDetails, $validated, $status, $transactionReference = null)
    {
        try {
            $invoice = $invoiceDetails['data']['invoice'] ?? [];
            $client = $invoice['client'] ?? [];
            
            // Get client_id from invoice data or find by client name/phone
            $clientId = null;
            if (isset($client['id'])) {
                // Try to find client by Kashtre client ID (stored in client_id field)
                $clientModel = \App\Models\Client::where('client_id', $client['client_id'] ?? null)
                    ->orWhere('phone_number', $client['phone_number'] ?? $invoice['client_phone'] ?? null)
                    ->orWhere('full_name', $invoice['client_name'] ?? null)
                    ->first();
                $clientId = $clientModel->id ?? null;
            } else {
                // Try to find client by name or phone
                if (isset($invoice['client_name']) || isset($invoice['client_phone'])) {
                    $clientModel = \App\Models\Client::where('full_name', $invoice['client_name'] ?? '')
                        ->orWhere('phone_number', $invoice['client_phone'] ?? null)
                        ->first();
                    $clientId = $clientModel->id ?? null;
                }
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
                // Mobile money reference will be generated by Yo Payments and updated later
                $paymentReference = 'MM-PENDING-' . strtoupper(Str::random(6));
            } else {
                // Bank transfer or cash - use provided reference or generate one
                $paymentReference = $validated['payment_reference'] ?? 'PAY-' . strtoupper(Str::random(8));
            }
            
            // Payment status is always pending initially
            // Will be updated to 'completed' when payment is confirmed/reviewed
            $paymentStatus = 'pending';
            
            // Create payment record
            $payment = Payment::create([
                'payment_reference' => $paymentReference,
                'invoice_id' => null, // Invoice is in Kashtre, not in third-party system
                'policy_id' => $policyId,
                'client_id' => $clientId,
                'payment_type' => 'invoice_payment',
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
                ],
                'processed_by' => auth()->id(),
            ]);
            
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
        } catch (\Exception $e) {
            Log::error('Failed to create payment record', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't fail the entire request if payment record creation fails
            return null;
        }
    }
}
