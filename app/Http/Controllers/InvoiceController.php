<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\KashtreApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        if (!$insuranceCompanyId) {
            return back()->with('error', 'No insurance company associated with your account.');
        }

        $result = $this->kashtreApi->markInvoiceAsPaid($invoiceId, $insuranceCompanyId, $validated);

        if ($result['success']) {
            return redirect()->route('invoices.show', $invoiceId)
                ->with('success', 'Payment cleared successfully.');
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
}
