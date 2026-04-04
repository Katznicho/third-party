<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthorizedVisit;
use App\Models\Client;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthorizedVisitController extends Controller
{
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
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $insuranceCompany = InsuranceCompany::find($request->insurance_company_id);
            if (!$insuranceCompany) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insurance company not found',
                ], 404);
            }

            // Find client by kashtre_client_id
            $client = Client::where('kashtre_client_id', $request->kashtre_client_id)
                ->where('insurance_company_id', $request->insurance_company_id)
                ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found or not synced yet',
                ], 404);
            }

            // Check if visit already registered
            $existingVisit = AuthorizedVisit::where('visit_id', $request->visit_id)
                ->where('client_id', $client->id)
                ->first();

            if ($existingVisit) {
                // Update existing visit
                $existingVisit->update([
                    'visit_date' => $request->visit_date,
                    'expiry_at' => $request->expiry_at,
                    'services_category' => $request->services_category,
                    'notes' => $request->notes,
                    'sync_data' => $request->all(),
                ]);

                Log::info('Authorized visit updated', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'visit_id' => $request->visit_id,
                    'client_id' => $client->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Authorized visit updated successfully',
                    'data' => [
                        'visit' => [
                            'id' => $existingVisit->id,
                            'visit_id' => $existingVisit->visit_id,
                            'client_id' => $existingVisit->client_id,
                            'status' => $existingVisit->status,
                            'visit_date' => $existingVisit->visit_date->toDateString(),
                            'expiry_at' => $existingVisit->expiry_at?->toDateTimeString(),
                        ],
                    ],
                ], 200);
            }

            // Create new authorized visit
            $visit = AuthorizedVisit::create([
                'client_id' => $client->id,
                'insurance_company_id' => $request->insurance_company_id,
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'visit_date' => $request->visit_date,
                'expiry_at' => $request->expiry_at,
                'status' => 'active',
                'services_category' => $request->services_category,
                'notes' => $request->notes,
                'sync_data' => $request->all(),
            ]);

            Log::info('Authorized visit registered', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'visit_id' => $request->visit_id,
                'client_id' => $client->id,
                'visit_id_model' => $visit->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Authorized visit registered successfully',
                'data' => [
                    'visit' => [
                        'id' => $visit->id,
                        'visit_id' => $visit->visit_id,
                        'client_id' => $visit->client_id,
                        'status' => $visit->status,
                        'visit_date' => $visit->visit_date->toDateString(),
                        'expiry_at' => $visit->expiry_at?->toDateTimeString(),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to register authorized visit', [
                'kashtre_client_id' => $request->kashtre_client_id ?? null,
                'visit_id' => $request->visit_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                    'visits' => $visits->map(function($visit) {
                        return [
                            'id' => $visit->id,
                            'visit_id' => $visit->visit_id,
                            'visit_date' => $visit->visit_date->toDateString(),
                            'expiry_at' => $visit->expiry_at?->toDateTimeString(),
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
