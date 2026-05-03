<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthorizedVisit;
use App\Models\Client;
use App\Models\InsuranceCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthorizedVisitController extends Controller
{
    /**
     * Session token + expiry from insurer "Visit authorization period (days)" setting.
     *
     * @return array{0: string, 1: \Carbon\Carbon}
     */
    protected function newVisitSession(InsuranceCompany $insuranceCompany, string $visitDate): array
    {
        $periodDays = max(1, min(365, (int) ($insuranceCompany->visit_authorization_period_days ?? 7)));
        $sessionExpiresAt = Carbon::parse($visitDate)->startOfDay()->addDays($periodDays)->endOfDay();
        $sessionCode = bin2hex(random_bytes(16));

        return [$sessionCode, $sessionExpiresAt];
    }

    /**
     * Register an authorized visit from Kashtre
     * 
     * This endpoint is called from Kashtre when a client registers a visit
     * to track authorized visits on the third-party vendor system.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            Log::info('API-VENDOR: registerAuthorizedVisit - Visit registration request received', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'insurance_company_id' => $request->insurance_company_id,
            ]);

            $validator = Validator::make($request->all(), [
                'kashtre_client_id' => 'required|string|max:255',
                'visit_id' => 'required|string|max:255',
                'insurance_company_id' => 'required|integer|exists:insurance_companies,id',
                'visit_date' => 'required|date',
                'expiry_at' => 'nullable|date_format:Y-m-d H:i:s',
                'services_category' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('API-VENDOR: registerAuthorizedVisit - Validation failed', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $insuranceCompany = InsuranceCompany::find($request->insurance_company_id);
            if (!$insuranceCompany) {
                Log::warning('API-VENDOR: registerAuthorizedVisit - Insurance company not found', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'insurance_company_id' => $request->insurance_company_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Insurance company not found',
                ], 404);
            }

            // Find client by kashtre_client_id
            Log::info('API-VENDOR: registerAuthorizedVisit - Looking up client', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'insurance_company_id' => $request->insurance_company_id,
            ]);

            $client = Client::where('kashtre_client_id', $request->kashtre_client_id)
                ->where('insurance_company_id', $request->insurance_company_id)
                ->first();

            if (!$client) {
                Log::warning('API-VENDOR: registerAuthorizedVisit - Client not found', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'insurance_company_id' => $request->insurance_company_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found or not synced yet',
                ], 404);
            }

            Log::info('API-VENDOR: registerAuthorizedVisit - Client found', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'vendor_client_id' => $client->id,
            ]);

            // Check if visit already registered (for this specific vendor)
            $existingVisit = AuthorizedVisit::where('visit_id', $request->visit_id)
                ->where('client_id', $client->id)
                ->where('insurance_company_id', $request->insurance_company_id)
                ->first();

            [$sessionCode, $sessionExpiresAt] = $this->newVisitSession($insuranceCompany, $request->visit_date);

            if ($existingVisit) {
                // Update existing visit
                Log::info('API-VENDOR: registerAuthorizedVisit - Updating existing visit', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'vendor_visit_id' => $existingVisit->id,
                ]);

                $existingVisit->update([
                    'visit_date' => $request->visit_date,
                    'expiry_at' => $request->expiry_at,
                    'session_code' => $sessionCode,
                    'session_expires_at' => $sessionExpiresAt,
                    'services_category' => $request->services_category,
                    'notes' => $request->notes,
                    'sync_data' => $request->all(),
                ]);
                $existingVisit->refresh();

                Log::info('API-VENDOR: registerAuthorizedVisit - Visit updated successfully', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'vendor_client_id' => $client->id,
                    'vendor_visit_id' => $existingVisit->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Authorized visit updated successfully',
                    'data' => [
                        'visit' => [
                            'id' => $existingVisit->id,
                            'visit_id' => $existingVisit->visit_id,
                            'client_id' => $existingVisit->client_id,
                            'kashtre_client_id' => $existingVisit->kashtre_client_id,
                            'status' => $existingVisit->status,
                            'visit_date' => $existingVisit->visit_date->toDateString(),
                            'expiry_at' => $existingVisit->expiry_at?->toDateTimeString(),
                            'session_code' => $existingVisit->session_code,
                            'session_expires_at' => $existingVisit->session_expires_at?->toDateTimeString(),
                            'visit_authorization_period_days' => $insuranceCompany->visit_authorization_period_days ?? 7,
                        ],
                    ],
                ], 200);
            }

            // Create new authorized visit
            Log::info('API-VENDOR: registerAuthorizedVisit - Creating new visit', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'vendor_client_id' => $client->id,
            ]);

            $visit = AuthorizedVisit::create([
                'client_id' => $client->id,
                'insurance_company_id' => $request->insurance_company_id,
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'session_code' => $sessionCode,
                'visit_date' => $request->visit_date,
                'expiry_at' => $request->expiry_at,
                'session_expires_at' => $sessionExpiresAt,
                'status' => 'active',
                'services_category' => $request->services_category,
                'notes' => $request->notes,
                'sync_data' => $request->all(),
            ]);

            Log::info('API-VENDOR: registerAuthorizedVisit - Visit created successfully', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'vendor_client_id' => $client->id,
                'vendor_visit_id' => $visit->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Authorized visit registered successfully',
                'data' => [
                    'visit' => [
                        'id' => $visit->id,
                        'visit_id' => $visit->visit_id,
                        'client_id' => $visit->client_id,
                        'kashtre_client_id' => $visit->kashtre_client_id,
                        'status' => $visit->status,
                        'visit_date' => $visit->visit_date->toDateString(),
                        'expiry_at' => $visit->expiry_at?->toDateTimeString(),
                        'session_code' => $visit->session_code,
                        'session_expires_at' => $visit->session_expires_at?->toDateTimeString(),
                        'visit_authorization_period_days' => $insuranceCompany->visit_authorization_period_days ?? 7,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('API-VENDOR: registerAuthorizedVisit - Exception', [
                'kashtre_client_id' => $request->kashtre_client_id ?? null,
                'visit_id' => $request->visit_id ?? null,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register authorized visit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authorized visits for a client
     * 
     * @param Request $request
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForClient(Request $request, $clientId)
    {
        try {
            $client = Client::find($clientId);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found',
                ], 404);
            }

            $visits = $client->authorizedVisits()
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'visits' => $visits->map(function ($visit) {
                        return [
                            'id' => $visit->id,
                            'visit_id' => $visit->visit_id,
                            'visit_date' => $visit->visit_date->toDateString(),
                            'expiry_at' => $visit->expiry_at?->toDateTimeString(),
                            'session_code' => $visit->session_code,
                            'session_expires_at' => $visit->session_expires_at?->toDateTimeString(),
                            'services_category' => $visit->services_category,
                            'status' => $visit->status,
                            'is_valid' => $visit->isValid(),
                        ];
                    }),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch authorized visits', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch authorized visits',
            ], 500);
        }
    }
}
