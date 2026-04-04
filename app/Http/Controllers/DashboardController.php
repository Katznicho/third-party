<?php

namespace App\Http\Controllers;

use App\Models\AuthorizedVisit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index(Request $request)
    {
        // Get date filter (default: today)
        $dateFilter = $request->query('date_filter', 'today');
        $customStartDate = $request->query('start_date');
        $customEndDate = $request->query('end_date');

        // Determine date range
        $now = now();
        $startDate = $now->clone()->startOfDay();
        $endDate = $now->clone()->endOfDay();

        switch ($dateFilter) {
            case 'today':
                $startDate = $now->clone()->startOfDay();
                $endDate = $now->clone()->endOfDay();
                break;
            case 'yesterday':
                $startDate = $now->clone()->subDay()->startOfDay();
                $endDate = $now->clone()->subDay()->endOfDay();
                break;
            case 'this_week':
                $startDate = $now->clone()->startOfWeek();
                $endDate = $now->clone()->endOfWeek();
                break;
            case 'this_month':
                $startDate = $now->clone()->startOfMonth();
                $endDate = $now->clone()->endOfMonth();
                break;
            case 'last_month':
                $startDate = $now->clone()->subMonth()->startOfMonth();
                $endDate = $now->clone()->subMonth()->endOfMonth();
                break;
            case 'last_30_days':
                $startDate = $now->clone()->subDays(30)->startOfDay();
                $endDate = $now->clone()->endOfDay();
                break;
            case 'custom':
                if ($customStartDate) {
                    $startDate = Carbon::parse($customStartDate)->startOfDay();
                }
                if ($customEndDate) {
                    $endDate = Carbon::parse($customEndDate)->endOfDay();
                }
                break;
        }

        $insuranceCompanyId = auth()->user()->insurance_company_id ?? 0;

        return view('dashboard.index', [
            'dateFilter' => $dateFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customStartDate' => $customStartDate,
            'customEndDate' => $customEndDate,
        ]);
    }

    /**
     * Show authorized visits tracking page.
     */
    public function authorizedVisits()
    {
        $authorizedVisits = AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return view('dashboard.authorized-visits', [
            'authorizedVisits' => $authorizedVisits,
        ]);
    }
}
