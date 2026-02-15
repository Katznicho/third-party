<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarzSmsService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.marzsms.base_url', 'https://sms.wearemarz.com/api/v1');
        $this->apiKey = config('services.marzsms.api_key');
        $this->apiSecret = config('services.marzsms.api_secret');
    }

    /**
     * Send SMS to one or multiple recipients
     * 
     * @param string|array $recipient Phone number(s) - can be single number or comma-separated string or array
     * @param string $message Message text (max 320 characters)
     * @return array
     */
    public function sendSms($recipient, string $message): array
    {
        try {
            // Normalize recipient format
            if (is_array($recipient)) {
                $recipient = implode(', ', $recipient);
            }

            // Format phone number for MarzSMS API (remove + sign, keep international format)
            $recipient = $this->formatPhoneForApi($recipient);

            // Ensure base URL doesn't have trailing slash
            $baseUrl = rtrim($this->baseUrl, '/');
            $url = "{$baseUrl}/sms/send";

            Log::info('MarzSmsService: Sending SMS', [
                'url' => $url,
                'recipient' => $recipient,
                'message_length' => strlen($message),
            ]);

            $httpClient = Http::timeout(30)
                ->withBasicAuth($this->apiKey, $this->apiSecret)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            // Disable SSL verification for local development if configured
            if (config('services.marzsms.verify_ssl', true) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->post($url, [
                    'recipient' => $recipient,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if the response indicates success
                $isSuccess = $data['success'] ?? ($response->status() === 200);
                
                Log::info('MarzSmsService: SMS API response', [
                    'recipient' => $recipient,
                    'http_status' => $response->status(),
                    'response_success' => $data['success'] ?? null,
                    'is_success' => $isSuccess,
                    'full_response' => $data,
                ]);
                
                if ($isSuccess) {
                    Log::info('MarzSmsService: SMS sent successfully', [
                        'recipient' => $recipient,
                        'remaining_balance' => $data['data']['remaining_balance'] ?? null,
                    ]);
                    return $data;
                } else {
                    // API returned 200 but success is false
                    Log::error('MarzSmsService: SMS API returned false success', [
                        'recipient' => $recipient,
                        'response' => $data,
                    ]);
                    return [
                        'success' => false,
                        'message' => $data['message'] ?? 'Failed to send SMS',
                        'error' => $data['error'] ?? 'send_failed',
                    ];
                }
            }

            $errorBody = $response->json() ?? $response->body();
            
            Log::error('MarzSmsService: Failed to send SMS', [
                'recipient' => $recipient,
                'status' => $response->status(),
                'error' => $errorBody,
            ]);

            return [
                'success' => false,
                'message' => $errorBody['message'] ?? "Failed to send SMS (HTTP {$response->status()})",
                'error' => $errorBody['error'] ?? 'send_failed',
            ];
        } catch (\Exception $e) {
            Log::error('MarzSmsService: Exception while sending SMS', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => 'exception',
            ];
        }
    }

    /**
     * Format phone number for MarzSMS API
     * Removes + sign and ensures international format (256XXXXXXXXX)
     * 
     * @param string|array $phone
     * @return string
     */
    protected function formatPhoneForApi($phone): string
    {
        if (is_array($phone)) {
            return implode(', ', array_map([$this, 'formatPhoneForApi'], $phone));
        }

        // Remove all non-digit characters except comma (for multiple recipients)
        $phone = preg_replace('/[^0-9,]/', '', $phone);
        
        // If starts with 256, keep as is
        if (strpos($phone, '256') === 0) {
            return $phone;
        }
        
        // If starts with 0, replace with 256
        if (strpos($phone, '0') === 0) {
            return '256' . substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Check account balance
     * 
     * @return array
     */
    public function getBalance(): array
    {
        try {
            $baseUrl = rtrim($this->baseUrl, '/');
            $url = "{$baseUrl}/account/balance";

            Log::info('MarzSmsService: Checking balance', [
                'url' => $url,
            ]);

            $httpClient = Http::timeout(30)
                ->withBasicAuth($this->apiKey, $this->apiSecret)
                ->acceptJson();

            // Disable SSL verification for local development if configured
            if (config('services.marzsms.verify_ssl', true) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('MarzSmsService: Balance retrieved successfully', [
                    'balance' => $data['data']['balance'] ?? null,
                    'currency' => $data['data']['currency'] ?? null,
                ]);
                return $data;
            }

            $errorBody = $response->json() ?? $response->body();
            
            Log::error('MarzSmsService: Failed to get balance', [
                'status' => $response->status(),
                'error' => $errorBody,
            ]);

            return [
                'success' => false,
                'message' => $errorBody['message'] ?? "Failed to get balance (HTTP {$response->status()})",
                'error' => $errorBody['error'] ?? 'balance_failed',
            ];
        } catch (\Exception $e) {
            Log::error('MarzSmsService: Exception while getting balance', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => 'exception',
            ];
        }
    }

    /**
     * Get SMS history with pagination
     * 
     * @param int $page Page number (default: 1)
     * @param int $perPage Items per page (default: 50, max: 100)
     * @return array
     */
    public function getHistory(int $page = 1, int $perPage = 50): array
    {
        try {
            $baseUrl = rtrim($this->baseUrl, '/');
            $url = "{$baseUrl}/sms/history";

            Log::info('MarzSmsService: Fetching SMS history', [
                'url' => $url,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $response = Http::timeout(30)
                ->withBasicAuth($this->apiKey, $this->apiSecret)
                ->acceptJson()
                ->get($url, [
                    'page' => $page,
                    'per_page' => min($perPage, 100), // Max 100 per API
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('MarzSmsService: History retrieved successfully', [
                    'page' => $page,
                    'total' => $data['data']['pagination']['total'] ?? 0,
                ]);
                return $data;
            }

            $errorBody = $response->json() ?? $response->body();
            
            Log::error('MarzSmsService: Failed to get history', [
                'status' => $response->status(),
                'error' => $errorBody,
            ]);

            return [
                'success' => false,
                'message' => $errorBody['message'] ?? "Failed to get history (HTTP {$response->status()})",
                'error' => $errorBody['error'] ?? 'history_failed',
            ];
        } catch (\Exception $e) {
            Log::error('MarzSmsService: Exception while getting history', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => 'exception',
            ];
        }
    }
}
