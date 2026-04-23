<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorTransactionRecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionSyncController extends Controller
{
    protected $recordingService;

    public function __construct(VendorTransactionRecordingService $recordingService)
    {
        $this->recordingService = $recordingService;
    }

    /**
     * Receive and record a transaction from Kashtre
     * POST /api/v1/transactions/record-from-kashtre
     */
    public function recordFromKashtre(Request $request)
    {
        try {
            Log::info('TransactionSyncController: Received transaction sync request from Kashtre', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            $data = $request->all();

            // Record the transaction
            $result = $this->recordingService->recordTransactionFromKashtre($data);

            if ($result['success']) {
                Log::info('TransactionSyncController: Transaction recorded successfully', [
                    'vendor_transaction_id' => $result['vendor_transaction_id'] ?? null,
                    'vendor_payment_id' => $result['vendor_payment_id'] ?? null,
                ]);

                return response()->json($result, 200);
            } else {
                Log::warning('TransactionSyncController: Failed to record transaction', $result);
                return response()->json($result, 400);
            }
        } catch (\Exception $e) {
            Log::error('TransactionSyncController: Exception during transaction sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error recording transaction: ' . $e->getMessage(),
            ], 500);
        }
    }
}
