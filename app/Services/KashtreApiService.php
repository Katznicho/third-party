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
        // Defaults to https://demo.kashtre.com unless KASHTRE_API_URL is set
        // For local development, set KASHTRE_API_URL=http://127.0.0.1:8002 in .env
        $this->baseUrl = config('services.kashtre.api_url', 'https://demo.kashtre.com');
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
                'RESPONSE_HEADERS' => $response->headers(),
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
            $baseUrl = rtrim($this->baseUrl, '/');
            $url = "{$baseUrl}/api/v1/invoices/{$invoiceId}/mark-paid";
            
            $payload = array_merge([
                'insurance_company_id' => $insuranceCompanyId,
            ], $data);
            
            // Ensure payment_method is included
            if (!isset($payload['payment_method'])) {
                $payload['payment_method'] = 'bank_transfer'; // Default
            }
            
            // Handle proof of payment file upload
            $multipart = [];
            $hasFile = false;
            
            if (isset($payload['proof_of_payment_path']) && $payload['proof_of_payment_path']) {
                $filePath = storage_path('app/public/' . $payload['proof_of_payment_path']);
                if (file_exists($filePath)) {
                    $hasFile = true;
                    $multipart[] = [
                        'name' => 'proof_of_payment',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ];
                    unset($payload['proof_of_payment_path']); // Remove from JSON payload
                }
            }
            
            // Add other fields as multipart
            foreach ($payload as $key => $value) {
                if ($key !== 'proof_of_payment') {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
            }
            
            Log::info('KashtreApiService: Marking invoice as paid', [
                'url' => $url,
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'payment_method' => $payload['payment_method'] ?? 'not set',
                'has_proof_of_payment' => $hasFile,
            ]);
            
            $http = Http::timeout(60); // Longer timeout for file uploads
            
            if ($hasFile) {
                // Use multipart for file upload
                $response = $http->asMultipart()
                    ->acceptJson()
                    ->post($url, $multipart);
            } else {
                // Use JSON for regular requests
                $response = $http->acceptJson()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, $payload);
            }

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to mark invoice as paid in Kashtre', [
                'invoice_id' => $invoiceId,
                'insurance_company_id' => $insuranceCompanyId,
                'status' => $response->status(),
                'error' => $response->json(),
                'response_body' => $response->body(),
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
                'trace' => $e->getTraceAsString(),
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

    /**
     * Notify Kashtre that a client-portion payment was recorded (so Kashtre can update its 2 sections:
     * Kashtre payments list and Kashtre client account statement).
     * Call this after recording the payment in the third-party app.
     *
     * @param array $payload insurance_company_id, policy_number, amount, payment_reference, [kashtre_invoice_id], [client_id], [mobile_money_number], [payment_date]
     * @return array{success: bool, message?: string}
     */
    /**
     * Notify Kashtre that an authorization decision has been made (approved/rejected)
     * so it can update the invoice and display the result to the user.
     */
    public function notifyAuthorizationDecision(array $payload): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = $baseUrl . '/api/v1/insurance/authorization-decision';

        try {
            Log::info('KashtreApiService: Sending authorization decision to Kashtre', [
                'url' => $url,
                'decision' => $payload['decision'] ?? null,
                'kashtre_invoice_id' => $payload['kashtre_invoice_id'] ?? null,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('KashtreApiService: Kashtre acknowledged authorization decision');
                return ['success' => true];
            }

            Log::warning('KashtreApiService: Kashtre authorization-decision callback failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Kashtre returned ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('KashtreApiService: Exception sending authorization decision to Kashtre', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function notifyClientPortionRecorded(array $payload)
    {
        $path = config('services.kashtre.record_client_portion_path', '/api/v1/third-party/client-portion-recorded');
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = $baseUrl . $path;

        try {
            Log::info('KashtreApiService: Notifying Kashtre of client-portion payment', [
                'url' => $url,
                'payment_reference' => $payload['payment_reference'] ?? null,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('KashtreApiService: Kashtre notified of client-portion payment');
                return ['success' => true];
            }

            Log::warning('KashtreApiService: Kashtre client-portion notification failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Kashtre returned ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('KashtreApiService: Exception notifying Kashtre of client-portion payment', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
