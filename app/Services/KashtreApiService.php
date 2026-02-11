<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KashtreApiService
{
    protected $baseUrl;

    public function __construct()
    {
        // Get the base URL from config
        // Defaults to https://kashtre.com in production
        // For local development, set KASHTRE_API_URL=http://127.0.0.1:8000 in .env
        $this->baseUrl = config('services.kashtre.api_url', 'https://kashtre.com');
    }

    /**
     * Get invoices for an insurance company
     */
    public function getInvoicesForInsuranceCompany($insuranceCompanyId)
    {
        try {
            $url = "{$this->baseUrl}/api/v1/invoices/insurance-company/{$insuranceCompanyId}";
            
            Log::info('KashtreApiService: Fetching invoices', [
                'url' => $url,
                'insurance_company_id' => $insuranceCompanyId,
            ]);

            $response = Http::timeout(30)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('KashtreApiService: Successfully fetched invoices', [
                    'insurance_company_id' => $insuranceCompanyId,
                    'invoice_count' => is_array($data['data'] ?? null) ? count($data['data']) : 0,
                ]);
                return $data;
            }

            $errorBody = $response->json() ?? $response->body();
            
            Log::error('Failed to fetch invoices from Kashtre', [
                'insurance_company_id' => $insuranceCompanyId,
                'url' => $url,
                'status' => $response->status(),
                'error' => $errorBody,
            ]);

            return [
                'success' => false,
                'message' => "Failed to fetch invoices from Kashtre (HTTP {$response->status()})",
                'data' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Exception while fetching invoices from Kashtre', [
                'insurance_company_id' => $insuranceCompanyId,
                'url' => $url ?? 'unknown',
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
