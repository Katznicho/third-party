<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KashtreApiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.kashtre.api_url', env('KASHTRE_API_URL', 'http://127.0.0.1:8000'));
    }

    /**
     * Get invoices for an insurance company
     */
    public function getInvoicesForInsuranceCompany($insuranceCompanyId)
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/api/v1/invoices/insurance-company/{$insuranceCompanyId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch invoices from Kashtre', [
                'insurance_company_id' => $insuranceCompanyId,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch invoices from Kashtre',
                'data' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Exception while fetching invoices from Kashtre', [
                'insurance_company_id' => $insuranceCompanyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Mark an invoice as paid
     */
    public function markInvoiceAsPaid($invoiceId, $insuranceCompanyId, $data = [])
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/api/v1/invoices/{$invoiceId}/mark-paid", array_merge([
                    'insurance_company_id' => $insuranceCompanyId,
                ], $data));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to mark invoice as paid in Kashtre', [
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to mark invoice as paid',
            ];
        } catch (\Exception $e) {
            Log::error('Exception while marking invoice as paid in Kashtre', [
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice details
     */
    public function getInvoiceDetails($invoiceId)
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/api/v1/invoices/{$invoiceId}/details");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch invoice details from Kashtre', [
                'invoice_id' => $invoiceId,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch invoice details',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while fetching invoice details from Kashtre', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
