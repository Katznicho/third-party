<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class VendorTransactionRecordingService
{
    /**
     * Record a transaction sent from Kashtre
     */
    public function recordTransactionFromKashtre($data)
    {
        try {
            Log::info('VendorTransactionRecordingService: Receiving transaction from Kashtre', [
                'kashtre_transaction_id' => $data['transaction_id'] ?? null,
                'kashtre_invoice_number' => $data['invoice_number'] ?? null,
                'amount' => $data['amount'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'insurance_company_id' => $data['insurance_company_id'] ?? null,
            ]);

            // Validate required fields - accept either vendor_id or insurance_company_id
            $vendorId = $data['vendor_id'] ?? $data['insurance_company_id'] ?? null;
            if (!$vendorId) {
                Log::warning('VendorTransactionRecordingService: Missing vendor_id or insurance_company_id', $data);
                return [
                    'success' => false,
                    'message' => 'Missing vendor_id or insurance_company_id',
                ];
            }

            // Create transaction in vendor system
            // Use unique transaction_number including vendor to avoid duplicates
            $uniqueTransactionNumber = 'KST-' . ($data['transaction_id'] ?? 'UNKNOWN') . '-' . ($data['vendor_id'] ?? 'V' . time());
            $vendorTransaction = Transaction::create([
                'transaction_number' => $uniqueTransactionNumber,
                'policy_id' => 1, // Use default policy for Kashtre synced transactions
                'reference_number' => $data['external_reference'] ?? $data['invoice_number'] ?? null,
                'description' => $data['description'] ?? 'Payment from Kashtre',
                'amount' => $data['amount'] ?? 0,
                'debit_amount' => $data['amount'] ?? 0,
                'credit_amount' => 0,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'transaction_status' => 'cleared',
                'metadata' => [
                    'kashtre_transaction_id' => $data['transaction_id'] ?? null,
                    'kashtre_invoice_id' => $data['invoice_id'] ?? null,
                    'kashtre_invoice_number' => $data['invoice_number'] ?? null,
                    'kashtre_client_id' => $data['client_id'] ?? null,
                    'kashtre_client_name' => $data['client_name'] ?? null,
                    'kashtre_business_id' => $data['business_id'] ?? null,
                    'vendor_id' => $data['vendor_id'] ?? null,
                    'vendor_name' => $data['vendor_name'] ?? null,
                    'authorization_reference' => $data['authorization_reference'] ?? null,
                    'insurance_portion' => $data['insurance_portion'] ?? 0,
                    'sync_timestamp' => now()->toIso8601String(),
                    'sync_type' => 'kashtre_auto_sync',
                ],
            ]);

            Log::info('VendorTransactionRecordingService: Transaction recorded successfully', [
                'vendor_transaction_id' => $vendorTransaction->id,
                'kashtre_transaction_id' => $data['transaction_id'] ?? null,
                'amount' => $data['amount'] ?? null,
            ]);

            // Create Invoice record from Kashtre data - make unique per vendor
            $vendorInvoice = \App\Models\Invoice::create([
                'invoice_number' => ($data['invoice_number'] ?? 'KST-' . $data['transaction_id']) . '-V' . ($data['vendor_id'] ?? time()),
                'policy_id' => 1, // Default policy
                'invoice_type' => 'claim',
                'description' => 'Payment from Kashtre - ' . ($data['description'] ?? 'Client Payment'),
                'invoice_date' => $data['transaction_date'] ?? now(),
                'due_date' => now()->addDays(30),
                'paid_date' => now(),
                'subtotal' => $data['amount'] ?? 0,
                'total_amount' => $data['amount'] ?? 0,
                'paid_amount' => $data['amount'] ?? 0,
                'balance_amount' => 0,
                'status' => 'paid',
                'notes' => 'Automatic sync from Kashtre - Client: ' . ($data['client_name'] ?? 'Unknown') . ', Insurance Portion: ' . ($data['insurance_portion'] ?? 0),
                'line_items' => [
                    [
                        'description' => 'Payment from Kashtre invoice ' . ($data['invoice_number'] ?? 'N/A'),
                        'amount' => $data['insurance_portion'] ?? 0,
                        'quantity' => 1,
                    ]
                ],
            ]);

            Log::info('VendorTransactionRecordingService: Invoice record created', [
                'vendor_invoice_id' => $vendorInvoice->id,
                'invoice_number' => $vendorInvoice->invoice_number,
                'kashtre_invoice_number' => $data['invoice_number'] ?? null,
            ]);

            // Also create a Payment record for payment tracking
            $vendorPayment = Payment::create([
                'payment_reference' => 'KST-' . ($data['external_reference'] ?? $data['transaction_id'] ?? time()) . '-' . ($data['vendor_id'] ?? 'V' . time()),
                'invoice_id' => $vendorInvoice->id,
                'client_id' => null, // Vendor doesn't track Kashtre client
                'payment_type' => 'premium_payment',
                'amount' => $data['amount'] ?? 0,
                'paid_amount' => $data['amount'] ?? 0,
                'balance_amount' => 0,
                'payment_method' => 'mobile_money',
                'mobile_money_provider' => 'kashtre',
                'transaction_id' => $vendorTransaction->id,
                'status' => 'completed',
                'payment_date' => $data['transaction_date'] ?? now(),
                'received_date' => now(),
                'cleared_date' => now(),
                'payment_notes' => 'Automatic sync from Kashtre - ' . ($data['description'] ?? ''),
                'payment_metadata' => [
                    'source' => 'kashtre',
                    'insurance_company_id' => $vendorId,
                    'connected_business_id' => $data['business_id'] ?? null,
                    'kashtre_transaction_id' => $data['transaction_id'] ?? null,
                    'kashtre_invoice_number' => $data['invoice_number'] ?? null,
                    'kashtre_client_name' => $data['client_name'] ?? null,
                    'kashtre_client_id' => $data['client_id'] ?? null,
                    'kashtre_business_id' => $data['business_id'] ?? null,
                    'authorization_reference' => $data['authorization_reference'] ?? null,
                    'insurance_portion' => $data['insurance_portion'] ?? 0,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $data['vendor_name'] ?? null,
                ],
            ]);

            Log::info('VendorTransactionRecordingService: Payment record created', [
                'vendor_payment_id' => $vendorPayment->id,
                'kashtre_transaction_id' => $data['transaction_id'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Transaction recorded successfully',
                'vendor_transaction_id' => $vendorTransaction->id,
                'vendor_invoice_id' => $vendorInvoice->id,
                'vendor_payment_id' => $vendorPayment->id,
            ];

        } catch (\Exception $e) {
            Log::error('VendorTransactionRecordingService: Error recording transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            return [
                'success' => false,
                'message' => 'Error recording transaction: ' . $e->getMessage(),
            ];
        }
    }
}
