<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Create or get client account for a client
     * 
     * This endpoint creates a client account in the third-party system
     * when a client is created in Kashtre with insurance.
     *
     * @param Request $request
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAccount(Request $request, $clientId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'insurance_company_id' => 'required|exists:insurance_companies,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Find the client
            $client = Client::find($clientId);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found',
                ], 404);
            }

            // Check if account already exists
            $account = $client->account;
            if ($account) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client account already exists',
                    'data' => [
                        'account' => [
                            'id' => $account->id,
                            'account_number' => $account->account_number,
                            'status' => $account->status,
                            'current_balance' => $account->current_balance,
                        ],
                    ],
                ]);
            }

            // Get insurance company
            $insuranceCompany = InsuranceCompany::find($request->insurance_company_id);
            if (!$insuranceCompany) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insurance company not found',
                ], 404);
            }

            // Generate account number
            $accountNumber = ClientAccount::generateAccountNumber($insuranceCompany);

            // Create account
            $account = ClientAccount::create([
                'client_id' => $client->id,
                'insurance_company_id' => $insuranceCompany->id,
                'account_number' => $accountNumber,
                'account_type' => $client->type === 'principal' ? 'individual' : 'individual',
                'status' => 'active',
                'opening_balance' => 0,
                'current_balance' => 0,
                'total_debits' => 0,
                'total_credits' => 0,
                'available_balance' => 0,
                'opened_date' => $client->created_at ?? now(),
                'auto_generate_statements' => true,
                'statement_frequency' => 'monthly',
            ]);

            Log::info('Client account created via API', [
                'client_id' => $client->id,
                'account_id' => $account->id,
                'account_number' => $accountNumber,
                'insurance_company_id' => $insuranceCompany->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client account created successfully',
                'data' => [
                    'account' => [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'status' => $account->status,
                        'current_balance' => $account->current_balance,
                        'opened_date' => $account->opened_date->toDateString(),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create client account via API', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create client account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync client from Kashtre to third-party system
     * 
     * This endpoint is called from Kashtre when a client is created via open enrollment
     * to sync the client details to the third-party vendor system.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncFromKashtre(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'kashtre_client_id' => 'required|string|max:255',
                'insurance_company_id' => 'required|integer|exists:insurance_companies,id',
                'first_name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'other_names' => 'nullable|string|max:255',
                'phone_number' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'marital_status' => 'nullable|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'nationality' => 'nullable|string|max:255',
                'policy_number' => 'nullable|string|max:255',
                'registered_via_open_enrollment' => 'required|boolean',
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

            // Check if client already exists (by kashtre_client_id or policy number)
            $existingClient = Client::where('kashtre_client_id', $request->kashtre_client_id)
                ->orWhere(function($query) use ($request) {
                    $query->where('policy_number', $request->policy_number)
                        ->where('insurance_company_id', $request->insurance_company_id);
                })
                ->first();

            if ($existingClient) {
                // Update existing client
                $existingClient->update([
                    'first_name' => $request->first_name,
                    'surname' => $request->surname,
                    'other_names' => $request->other_names,
                    'cell_phone' => $request->phone_number,
                    'email' => $request->email,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'marital_status' => $request->marital_status,
                    'occupation' => $request->occupation,
                    'nationality' => $request->nationality,
                    'policy_number' => $request->policy_number,
                    'insurance_company_id' => $request->insurance_company_id,
                    'registered_via_open_enrollment' => $request->registered_via_open_enrollment,
                    'is_active' => true,
                ]);

                Log::info('Client updated via Kashtre sync', [
                    'kashtre_client_id' => $request->kashtre_client_id,
                    'client_id' => $existingClient->id,
                    'registered_via_open_enrollment' => $request->registered_via_open_enrollment,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Client updated successfully',
                    'data' => [
                        'client' => [
                            'id' => $existingClient->id,
                            'kashtre_client_id' => $existingClient->kashtre_client_id,
                            'full_name' => $existingClient->full_name,
                            'policy_number' => $existingClient->policy_number,
                            'registered_via_open_enrollment' => $existingClient->registered_via_open_enrollment,
                        ],
                    ],
                ], 200);
            }

            // Create new client
            $client = Client::create([
                'kashtre_client_id' => $request->kashtre_client_id,
                'type' => 'principal',
                'insurance_company_id' => $request->insurance_company_id,
                'first_name' => $request->first_name,
                'surname' => $request->surname,
                'other_names' => $request->other_names,
                'cell_phone' => $request->phone_number,
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'occupation' => $request->occupation,
                'nationality' => $request->nationality,
                'policy_number' => $request->policy_number,
                'registered_via_open_enrollment' => $request->registered_via_open_enrollment,
                'is_active' => true,
            ]);

            Log::info('Client created via Kashtre sync', [
                'kashtre_client_id' => $request->kashtre_client_id,
                'client_id' => $client->id,
                'registered_via_open_enrollment' => $request->registered_via_open_enrollment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client synced successfully',
                'data' => [
                    'client' => [
                        'id' => $client->id,
                        'kashtre_client_id' => $client->kashtre_client_id,
                        'full_name' => $client->full_name,
                        'policy_number' => $client->policy_number,
                        'registered_via_open_enrollment' => $client->registered_via_open_enrollment,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to sync client from Kashtre', [
                'kashtre_client_id' => $request->kashtre_client_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
