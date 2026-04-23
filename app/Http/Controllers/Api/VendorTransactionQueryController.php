<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VendorTransactionQueryController extends Controller
{
    /**
     * Get transactions synced for a specific vendor/insurance company
     * GET /api/v1/transactions/by-vendor/{vendorId}
     */
    public function byVendor($vendorId)
    {
        try {
            Log::info('VendorTransactionQueryController: Fetching transactions by vendor', [
                'vendor_id' => $vendorId,
            ]);

            // Get transactions where metadata.vendor_id = $vendorId
            $transactions = Transaction::where(
                \Illuminate\Support\Facades\DB::raw('JSON_EXTRACT(metadata, "$.vendor_id")'),
                '=',
                $vendorId
            )
            ->with(['policy', 'client'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->transaction_status,
                    'transaction_date' => $transaction->transaction_date,
                    'created_at' => $transaction->created_at,
                    'kashtre_invoice_number' => $transaction->metadata['kashtre_invoice_number'] ?? null,
                    'kashtre_client_name' => $transaction->metadata['kashtre_client_name'] ?? null,
                    'kashtre_transaction_id' => $transaction->metadata['kashtre_transaction_id'] ?? null,
                ];
            });

            Log::info('VendorTransactionQueryController: Retrieved transactions', [
                'vendor_id' => $vendorId,
                'count' => $transactions->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'count' => $transactions->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('VendorTransactionQueryController: Error fetching transactions', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get invoices synced for a specific vendor/insurance company
     * GET /api/v1/invoices/by-vendor/{vendorId}
     */
    public function invoicesByVendor($vendorId)
    {
        try {
            Log::info('VendorTransactionQueryController: Fetching invoices by vendor', [
                'vendor_id' => $vendorId,
            ]);

            // Get all invoices and filter by vendor in notes (contains vendor name)
            // Or we could join with transactions that have vendor_id in metadata
            $invoices = \App\Models\Invoice::all()
                ->filter(function ($invoice) use ($vendorId) {
                    // Check if this invoice has transactions for this vendor
                    $hasVendorTransaction = \App\Models\Transaction::where(
                        \Illuminate\Support\Facades\DB::raw('JSON_EXTRACT(metadata, "$.vendor_id")'),
                        '=',
                        $vendorId
                    )
                    ->where(function ($q) use ($invoice) {
                        // Match by transaction or via payment relationship
                        $q->whereHas('payments', fn ($p) => $p->where('invoice_id', $invoice->id))
                          ->orWhere(function ($q2) use ($invoice) {
                              $q2->where('id', $invoice->id);
                          });
                    })
                    ->exists();
                    
                    return $hasVendorTransaction;
                })
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => (float) $invoice->total_amount,
                        'paid_amount' => (float) ($invoice->paid_amount ?? 0),
                        'balance_amount' => (float) ($invoice->balance_amount ?? 0),
                        'status' => $invoice->status,
                        'invoice_date' => $invoice->invoice_date,
                        'created_at' => $invoice->created_at,
                        'notes' => $invoice->notes,
                    ];
                })
                ->values();

            Log::info('VendorTransactionQueryController: Retrieved invoices', [
                'vendor_id' => $vendorId,
                'count' => $invoices->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'count' => $invoices->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('VendorTransactionQueryController: Error fetching invoices', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching invoices: ' . $e->getMessage(),
            ], 500);
        }
    }
}
