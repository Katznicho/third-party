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
            // Ensure base URL doesn't have trailing slash
            $baseUrl = rtrim($this->baseUrl, '/');
            $url = "{$baseUrl}/api/v1/invoices/insurance-company/{$insuranceCompanyId}";
            
            // Log the endpoint URL prominently for debugging
            Log::error('KashtreApiService: ATTEMPTING TO FETCH INVOICES', [
                'ENDPOINT_URL' => $url,
                'BASE_URL_CONFIG' => $this->baseUrl,
                'CLEANED_BASE_URL' => $baseUrl,
                'INSURANCE_COMPANY_ID' => $insuranceCompanyId,
                'ENV_KASHTRE_API_URL' => env('KASHTRE_API_URL', 'NOT SET - using default'),
                'CONFIG_SERVICES_KASHTRE_API_URL' => config('services.kashtre.api_url', 'NOT SET'),
            ]);
            
            Log::info('KashtreApiService: Fetching invoices', [
                'url' => $url,
                'base_url' => $this->baseUrl,
                'cleaned_base_url' => $baseUrl,
                'insurance_company_id' => $insuranceCompanyId,
            ]);

            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($url);

            Log::info('KashtreApiService: Response received', [
                'url' => $url,
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('KashtreApiService: Successfully fetched invoices', [
                    'insurance_company_id' => $insuranceCompanyId,
                    'invoice_count' => is_array($data['data'] ?? null) ? count($data['data']) : 0,
                ]);
                return $data;
            }

            $errorBody = $response->json() ?? $response->body();
            $responseBody = $response->body();
            
            // Log error with full details
            Log::error('KashtreApiService: FAILED TO FETCH INVOICES - 404 ERROR', [
                'ENDPOINT_URL_CALLED' => $url,
                'HTTP_STATUS' => $response->status(),
                'INSURANCE_COMPANY_ID' => $insuranceCompanyId,
                'BASE_URL_CONFIG' => $this->baseUrl,
                'ENV_KASHTRE_API_URL' => env('KASHTRE_API_URL', 'NOT SET'),
                'ERROR_RESPONSE' => $errorBody,
                'RESPONSE_BODY_PREVIEW' => substr($responseBody, 0, 1000),
                'FULL_RESPONSE_BODY' => $responseBody,
                'RESPONSE_HEADERS' => $response->headers()->all(),
            ]);
            
            Log::error('Failed to fetch invoices from Kashtre', [
                'insurance_company_id' => $insuranceCompanyId,
                'url' => $url,
                'base_url' => $this->baseUrl,
                'status' => $response->status(),
                'error' => $errorBody,
                'response_body' => substr($responseBody, 0, 1000),
                'full_response' => $responseBody,
            ]);

            // Provide more helpful error message based on status code
            $errorMessage = "Failed to fetch invoices from Kashtre (HTTP {$response->status()})";
            if ($response->status() === 404) {
                $errorMessage = "Invoice endpoint not found. The endpoint '{$url}' does not exist on the Kashtre API. Please verify the endpoint path is correct.";
            } elseif ($response->status() === 401 || $response->status() === 403) {
                $errorMessage = "Authentication failed. Please check if the Kashtre API requires authentication.";
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => [],
                'debug' => [
                    'url' => $url,
                    'status' => $response->status(),
                    'response_preview' => substr($responseBody, 0, 200),
                ],
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
