<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessConnection;
use App\Models\ConnectedCompanyItemCoverage;
use Illuminate\Http\JsonResponse;

class ConnectedCompanyItemCoverageController extends Controller
{
    /**
     * Item coverage rules for a Kashtre provider + insurer (default 100% when not listed).
     */
    public function index(int $connectedBusinessId, int $insuranceCompanyId): JsonResponse
    {
        $connection = BusinessConnection::query()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->where('connected_business_id', $connectedBusinessId)
            ->first();

        if (! $connection) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $rows = ConnectedCompanyItemCoverage::query()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->where('business_connection_id', $connection->id)
            ->where('is_active', true)
            ->where('coverage_percent', '<', 100)
            ->orderBy('service_code')
            ->get(['service_code', 'coverage_percent', 'reason']);

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($row) => [
                'service_code' => $row->service_code,
                'coverage_percent' => (float) $row->coverage_percent,
                'reason' => $row->reason,
            ])->values()->all(),
        ]);
    }
}
