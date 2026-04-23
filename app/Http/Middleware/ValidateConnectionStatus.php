<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BusinessConnection;
use Illuminate\Http\JsonResponse;

class ValidateConnectionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get insurance company ID from request - accept both vendor_id and insurance_company_id
        $insuranceCompanyId = $request->input('insurance_company_id') ?? $request->input('vendor_id');
        $connectedBusinessId = $request->input('connected_business_id');

        if (!$insuranceCompanyId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing insurance_company_id or vendor_id in request',
            ], 400);
        }

        // Find the business connection
        $connection = BusinessConnection::where('insurance_company_id', $insuranceCompanyId);

        if ($connectedBusinessId) {
            $connection = $connection->where('connected_business_id', $connectedBusinessId);
        }

        $connection = $connection->first();

        // If connection doesn't exist, allow the request to proceed (will be handled by business logic)
        if (!$connection) {
            return $next($request);
        }

        // Check if connection is suspended or blocked
        if ($connection->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'This provider connection is currently suspended and cannot process requests.',
                'status' => 'suspended',
                'reason' => $connection->block_reason,
                'suspended_at' => $connection->blocked_at?->toIso8601String(),
            ], 403);
        }

        if ($connection->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'This provider connection is blocked and cannot process requests.',
                'status' => 'blocked',
                'reason' => $connection->block_reason,
                'blocked_at' => $connection->blocked_at?->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}
