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
}
