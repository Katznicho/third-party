<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KashtreApiService
{
    /** Kashtre application root (scheme + host + port only — never ends with /api). */
    protected $baseUrl;

    public function __construct()
    {
        // Defaults to https://demo.kashtre.com unless KASHTRE_API_URL is set.
        // Local: KASHTRE_API_URL=http://127.0.0.1:8000 (same machine as insurer portal on :8001).
        // Common mistake: .../api or .../api/v1 — that would build .../api/api/v1/... and Laravel returns "route could not be found".
        $raw = (string) config('services.kashtre.api_url', 'https://demo.kashtre.com');
        $this->baseUrl = $this->normalizeKashtreBaseUrl($raw);
    }

    /**
     * Strip accidental /api or /api/v1 suffix so callers always append /api/v1/... once.
     */
    protected function normalizeKashtreBaseUrl(string $url): string
    {
        $url = rtrim($url, '/');
        if ($url === '') {
            return 'https://demo.kashtre.com';
        }

        foreach (['/api/v1', '/api'] as $suffix) {
            if (str_ends_with($url, $suffix)) {
                $url = rtrim(substr($url, 0, -strlen($suffix)), '/');
            }
        }

        return $url !== '' ? $url : 'https://demo.kashtre.com';
    }

    /**
     * Ledger summary, recent transactions, invoices, and exclusions (mirrors Kashtre third-party vendor show).
     *
     * @return array{success: bool, data: ?array, error?: string, http_status?: ?int}
     */
    public function getInsurerPortalVendorSummary(int $businessId, int $thirdPartyVendorId): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendors/{$thirdPartyVendorId}/insurer-portal-summary";

        try {
            $response = Http::timeout(30)->acceptJson()->get($url);

            return $this->interpretInsurerPortalJsonResponse($response, $url);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: insurer-portal-summary exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $this->connectionErrorMessage($url, $e->getMessage()),
                'http_status' => null,
            ];
        }
    }

    /**
     * Paginated balance history for the insurer portal full statement.
     *
     * @return array{success: bool, data: ?array, error?: string, http_status?: ?int}
     */
    public function getInsurerPortalBalanceHistory(int $businessId, int $thirdPartyVendorId, int $page = 1, int $perPage = 50): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendors/{$thirdPartyVendorId}/insurer-portal-balance-history"
            . '?' . http_build_query([
                'page' => max(1, $page),
                'per_page' => min(max($perPage, 1), 100),
            ]);

        try {
            $response = Http::timeout(30)->acceptJson()->get($url);

            return $this->interpretInsurerPortalJsonResponse($response, $url);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: insurer-portal-balance-history exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $this->connectionErrorMessage($url, $e->getMessage()),
                'http_status' => null,
            ];
        }
    }

    /**
     * Preview service charge on an insurer payment to a connected provider.
     *
     * @return array{success: bool, data: ?array, error?: string, http_status?: ?int}
     */
    public function previewInsurerPortalPayment(int $businessId, int $thirdPartyVendorId, float $amount, array $historyIds = []): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendors/{$thirdPartyVendorId}/insurer-portal-payment/preview";

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($url, [
                    'amount' => $amount,
                    'history_ids' => array_values(array_map('intval', $historyIds)),
                ]);

            return $this->interpretInsurerPortalJsonResponse($response, $url);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: insurer-portal-payment preview exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $this->connectionErrorMessage($url, $e->getMessage()),
                'http_status' => null,
            ];
        }
    }

    /**
     * Record insurer payment on Kashtre third-party payer ledger (includes service charge debit).
     *
     * @param  array{amount: float, payment_method: string, reference?: ?string, notes?: ?string}  $payload
     * @return array{success: bool, data: ?array, error?: string, http_status?: ?int}
     */
    public function recordInsurerPortalPayment(int $businessId, int $thirdPartyVendorId, array $payload): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendors/{$thirdPartyVendorId}/insurer-portal-payment";

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->post($url, $payload);

            return $this->interpretInsurerPortalJsonResponse($response, $url);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: insurer-portal-payment exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $this->connectionErrorMessage($url, $e->getMessage()),
                'http_status' => null,
            ];
        }
    }

    /**
     * @return array{success: bool, data: ?array, error?: string, http_status?: ?int}
     */
    protected function interpretInsurerPortalJsonResponse(Response $response, string $url): array
    {
        $json = $response->json();
        $status = $response->status();

        if ($response->successful() && is_array($json) && ($json['success'] ?? false) === true) {
            return [
                'success' => true,
                'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                'http_status' => $status,
            ];
        }

        $message = null;
        if (is_array($json) && !empty($json['message'])) {
            $message = (string) $json['message'];
        }

        if ($message === null) {
            if (!is_array($json)) {
                $message = sprintf(
                    'Kashtre returned HTTP %d with a non-JSON response. Set KASHTRE_API_URL in .env to your Kashtre app root (no trailing path), e.g. http://127.0.0.1:8000. On Kashtre run php artisan route:clear after deploying API routes.',
                    $status
                );
            } elseif ($status === 404) {
                $message = 'Kashtre returned 404. Either no clinic exists with that business id in Kashtre (check connected_business_id), or the insurer-portal API is missing on that Kashtre deploy (run php artisan route:clear there). If the message mentions "route could not be found", check KASHTRE_API_URL is only the app root (e.g. http://127.0.0.1:8000) with no /api suffix — otherwise URLs become /api/api/v1/...';
            } else {
                $message = sprintf('Kashtre returned HTTP %d.', $status);
            }
        }

        if (config('app.debug')) {
            $message .= ' Requested URL: '.$url;
        }

        Log::warning('KashtreApiService: insurer portal HTTP response not successful', [
            'url' => $url,
            'status' => $status,
            'body_preview' => substr($response->body(), 0, 800),
        ]);

        return [
            'success' => false,
            'data' => null,
            'error' => $message,
            'http_status' => $status,
            'response_body' => substr($response->body(), 0, 500),
        ];
    }

    protected function connectionErrorMessage(string $url, string $exceptionMessage): string
    {
        $base = rtrim($this->baseUrl, '/');
        $msg = "Could not reach Kashtre at {$base}. {$exceptionMessage}. "
            .'Set KASHTRE_API_URL in .env to the Kashtre application URL (same host/port as the provider app; no /api suffix).';

        if (config('app.debug')) {
            $msg .= ' Full URL: '.$url;
        }

        return $msg;
    }

    /**
     * Vendor service charge tiers for a connected clinic (saved + recommended defaults + effective schedule).
     *
     * @return array<string, mixed>|null
     */
    public function getVendorServiceCharges(int $businessId, ?int $thirdPartyVendorId = null): ?array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendor-service-charges";
        if ($thirdPartyVendorId !== null) {
            $url .= '?third_party_vendor_id='.$thirdPartyVendorId;
        }

        return $this->getJsonDataOrNull($url, 'vendor service charges');
    }

    /**
     * Recommended default vendor service charge tiers (Kashtre config template).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVendorServiceChargeRecommendedDefaults(int $businessId): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendor-service-charges/recommended-defaults";

        $payload = $this->getJsonDataOrNull($url, 'vendor service charge defaults');

        return is_array($payload['recommended_defaults'] ?? null)
            ? $payload['recommended_defaults']
            : [];
    }

    /**
     * Effective vendor service charge schedule for one insurer (third-party vendor id).
     *
     * @return array<string, mixed>|null
     */
    public function getVendorServiceChargesForVendor(int $businessId, int $thirdPartyVendorId): ?array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendors/{$thirdPartyVendorId}/service-charges";

        return $this->getJsonDataOrNull($url, 'vendor service charges for vendor');
    }

    /**
     * Calculate Kashtre third-party vendor service charge for a subtotal.
     *
     * @return array<string, mixed>|null
     */
    public function calculateVendorServiceCharge(int $businessId, float $subtotal, ?int $thirdPartyVendorId = null): ?array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-vendor-service-charges/calculate";

        $body = ['subtotal' => $subtotal];
        if ($thirdPartyVendorId !== null) {
            $body['third_party_vendor_id'] = $thirdPartyVendorId;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($url, $body);

            if ($response->successful() && ($response->json('success') ?? false)) {
                return $response->json('data');
            }

            Log::warning('KashtreApiService: calculate vendor service charge failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: calculate vendor service charge exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getJsonDataOrNull(string $url, string $label): ?array
    {
        try {
            $response = Http::timeout(20)->acceptJson()->get($url);

            if ($response->successful() && ($response->json('success') ?? false)) {
                $data = $response->json('data');

                return is_array($data) ? $data : null;
            }

            Log::warning("KashtreApiService: Failed to fetch {$label}", [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error("KashtreApiService: Exception while fetching {$label}", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get excluded items for a specific Kashtre business and insurance company.
     */

    public function getExcludedItemsForProvider(int $businessId, int $insuranceCompanyId): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/third-party-payers/{$insuranceCompanyId}/excluded-items";

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::warning('KashtreApiService: Failed to fetch excluded items', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: Exception while fetching excluded items', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Get all items for a specific Kashtre business.
     */
    public function getItemsForBusiness(int $businessId): array
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = "{$baseUrl}/api/v1/businesses/{$businessId}/items";

        try {
            Log::info('KashtreApiService: Fetching items for business', [
                'url' => $url,
                'business_id' => $businessId,
            ]);

            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                Log::info('KashtreApiService: Items fetched from Kashtre', [
                    'business_id' => $businessId,
                    'count' => is_array($data) ? count($data) : 0,
                ]);
                return $data;
            }

            Log::warning('KashtreApiService: Failed to fetch items from Kashtre', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('KashtreApiService: Exception while fetching items from Kashtre', [
                'url' => $url,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
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
