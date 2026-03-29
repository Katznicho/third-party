<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Transaction;
use App\Services\KashtreApiService;
use App\Services\RecordClientPortionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Create a payment responsibility payment (deductible or co-pay)
     * This is called by Kashtre when a client pays their deductible or co-pay
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentResponsibility(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id' => 'nullable|exists:clients,id',
                'insurance_company_id' => 'required|exists:insurance_companies,id',
                'payment_type' => 'required|in:deductible_payment,copay_payment',
                'amount' => 'required|numeric|min:0.01',
                'paid_amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,card,credit,other',
                'mobile_money_number' => 'nullable|string|max:255',
                'transaction_id' => 'nullable|string|max:255',
                'payment_reference' => 'required|string|max:255|unique:payments,payment_reference',
                'payment_date' => 'required|date',
                'payment_notes' => 'nullable|string|max:1000',
                'status' => 'nullable|in:pending,processing,completed,failed,cancelled,refunded,reversed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            DB::beginTransaction();

            // Find client if client_id is provided
            $client = null;
            $policyId = null;
            if ($validated['client_id']) {
                $client = Client::find($validated['client_id']);
                if ($client) {
                    // Find policy for this client and insurance company
                    $policy = $client->policies()
                        ->where('insurance_company_id', $validated['insurance_company_id'])
                        ->where('status', 'active')
                        ->first();
                    
                    if ($policy) {
                        $policyId = $policy->id;
                    }
                }
            }

            // If no policy found, try to find any policy for this insurance company
            // This is a fallback - ideally we should have the policy
            if (!$policyId && $validated['client_id']) {
                $client = Client::find($validated['client_id']);
                if ($client) {
                    // Try to find any active policy for this insurance company
                    $anyPolicy = \App\Models\Policy::where('insurance_company_id', $validated['insurance_company_id'])
                        ->where('status', 'active')
                        ->first();
                    
                    if ($anyPolicy) {
                        $policyId = $anyPolicy->id;
                        \Illuminate\Support\Facades\Log::warning('Using fallback policy for payment responsibility payment', [
                            'client_id' => $validated['client_id'],
                            'policy_id' => $policyId,
                        ]);
                    }
                }
            }
            
            // Create payment record (policy_id may be nullable depending on migration)
            $paymentData = [
                'payment_reference' => $validated['payment_reference'],
                'invoice_id' => null, // No invoice for payment responsibility
                'client_id' => $validated['client_id'],
                'payment_type' => 'full_payment', // Use existing enum value
                'amount' => $validated['amount'],
                'paid_amount' => $validated['paid_amount'],
                'balance_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'payment_date' => $validated['payment_date'],
                'received_date' => now(),
                'payment_notes' => ($validated['payment_notes'] ?? '') . ' - Payment responsibility: ' . str_replace('_', ' ', $validated['payment_type']),
                'payment_metadata' => [
                    'payment_responsibility_type' => $validated['payment_type'],
                    'insurance_company_id' => $validated['insurance_company_id'],
                    'source' => 'kashtre',
                ],
                'processed_by' => null, // API call, no user
            ];
            
            // Add policy_id if found (nullable based on migration)
            $paymentData['policy_id'] = $policyId;
            
            $payment = Payment::create($paymentData);

            // Update client account if client exists
            if ($client) {
                $account = $client->account;
                if (!$account) {
                    // Create account if it doesn't exist
                    $insuranceCompany = \App\Models\InsuranceCompany::find($validated['insurance_company_id']);
                    if ($insuranceCompany) {
                        $accountNumber = ClientAccount::generateAccountNumber($insuranceCompany);
                        $account = ClientAccount::create([
                            'client_id' => $client->id,
                            'insurance_company_id' => $validated['insurance_company_id'],
                            'account_number' => $accountNumber,
                            'account_type' => 'individual',
                            'status' => 'active',
                            'opening_balance' => 0,
                            'current_balance' => 0,
                            'total_debits' => 0,
                            'total_credits' => 0,
                            'available_balance' => 0,
                            'opened_date' => now(),
                        ]);
                    }
                }

                if ($account) {
                    // Determine transaction type based on payment type
                    $transactionType = $validated['payment_type'] === 'deductible_payment' ? 'deductible' : 'copayment';
                    
                    // Use policyId if found, otherwise try to find any policy for this insurance company
                    $transactionPolicyId = $policyId;
                    if (!$transactionPolicyId) {
                        $fallbackPolicy = \App\Models\Policy::where('insurance_company_id', $validated['insurance_company_id'])
                            ->where('status', 'active')
                            ->first();
                        if ($fallbackPolicy) {
                            $transactionPolicyId = $fallbackPolicy->id;
                        }
                    }
                    
                    // Create transaction record for client account (policy_id is required)
                    if ($transactionPolicyId) {
                        $transaction = Transaction::create([
                            'client_id' => $client->id,
                            'policy_id' => $transactionPolicyId,
                            'invoice_id' => null,
                            'payment_id' => $payment->id,
                            'type' => $transactionType,
                            'transaction_date' => now(),
                            'transaction_number' => 'TXN-' . strtoupper(Str::random(8)) . '-' . time(),
                            'description' => ucfirst(str_replace('_', ' ', $validated['payment_type'])) . ' payment',
                            'reference_number' => $validated['payment_reference'],
                            'amount' => $validated['amount'],
                            'debit_amount' => 0,
                            'credit_amount' => $validated['amount'],
                            'balance_before' => $account->current_balance ?? 0,
                            'balance_after' => ($account->current_balance ?? 0) + $validated['amount'],
                            'transaction_status' => 'cleared',
                            'payment_method' => $validated['payment_method'],
                            'service_category_id' => null,
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Cannot create transaction: No policy found', [
                            'client_id' => $client->id,
                            'insurance_company_id' => $validated['insurance_company_id'],
                        ]);
                        $transaction = null;
                    }

                    // Update account balances (only if transaction was created)
                    if ($transaction) {
                        $account->update([
                            'current_balance' => ($account->current_balance ?? 0) + $validated['amount'],
                            'total_credits' => ($account->total_credits ?? 0) + $validated['amount'],
                            'available_balance' => ($account->current_balance ?? 0) + $validated['amount'],
                            'last_transaction_date' => now(),
                        ]);

                        Log::info('Client account updated with payment responsibility payment', [
                            'account_id' => $account->id,
                            'client_id' => $client->id,
                            'amount' => $validated['amount'],
                            'transaction_id' => $transaction->id,
                        ]);
                    } else {
                        // Still update account balance even if transaction wasn't created
                        $account->update([
                            'current_balance' => ($account->current_balance ?? 0) + $validated['amount'],
                            'total_credits' => ($account->total_credits ?? 0) + $validated['amount'],
                            'available_balance' => ($account->current_balance ?? 0) + $validated['amount'],
                            'last_transaction_date' => now(),
                        ]);
                        
                        Log::warning('Client account updated but transaction not created (no policy found)', [
                            'account_id' => $account->id,
                            'client_id' => $client->id,
                            'amount' => $validated['amount'],
                        ]);
                    }
                }
            }

            DB::commit();

            Log::info('Payment responsibility payment created successfully', [
                'payment_id' => $payment->id,
                'payment_reference' => $validated['payment_reference'],
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment responsibility payment created successfully',
                'data' => [
                    'payment' => $payment,
                    'transaction_id' => $transaction->id ?? null,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create payment responsibility payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment responsibility payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record client portion payment (from Kashtre when "Collect payment" is completed).
     * Looks up client by policy_number + insurance_company_id, then creates Payment, Transaction, and updates ClientAccount
     * so the payment reflects on the third-party client account and in Payments.
     */
    public function recordClientPortionPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'insurance_company_id' => 'required|exists:insurance_companies,id',
                'policy_number' => 'required|string|max:64',
                'amount' => 'required|numeric|min:0.01',
                'payment_reference' => 'required|string|max:255|unique:payments,payment_reference',
                'kashtre_invoice_id' => 'nullable|string|max:64',
                'invoice_number' => 'nullable|string|max:64',
                'authorization_reference' => 'nullable|string|max:64',
                'connected_business_id' => 'nullable|integer', // Kashtre business id – used to show payment on /third-party-vendors/{id}
                'payment_method' => 'nullable|in:cash,bank_transfer,mobile_money,cheque,card,credit,other',
                'mobile_money_number' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $result = RecordClientPortionService::record($validated);

            if (!$result['success']) {
                $status = ($result['message'] ?? '') === 'Policy not found for this insurance company and policy number.'
                    ? 404
                    : 422;
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to record',
                ], $status);
            }

            // Notify Kashtre (external) so it can update its 2 sections: payments + client account statement
            $kashtrePayload = [
                'insurance_company_id' => $validated['insurance_company_id'],
                'policy_number' => $validated['policy_number'],
                'amount' => $validated['amount'],
                'payment_reference' => $validated['payment_reference'],
                'client_id' => $result['client_id'],
                'kashtre_invoice_id' => $validated['kashtre_invoice_id'] ?? null,
                'authorization_reference' => $validated['authorization_reference'] ?? null,
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'payment_date' => $validated['payment_date'] ?? now()->format('Y-m-d'),
            ];
            $notify = app(KashtreApiService::class)->notifyClientPortionRecorded($kashtrePayload);
            if (!$notify['success']) {
                Log::warning('[ThirdParty] Kashtre was not notified of client-portion payment', $notify);
            }

            return response()->json([
                'success' => true,
                'message' => 'Client portion payment recorded. Reflected on client account and in Payments.',
                'data' => [
                    'payment_id' => $result['payment']->id,
                    'transaction_id' => $result['transaction']?->id,
                    'client_id' => $result['client_id'],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('[ThirdParty] recordClientPortionPayment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record client portion payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
