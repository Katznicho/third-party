<?php

namespace App\Http\Controllers;

use App\Models\AuthorizedVisit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index()
    {
        return view('dashboard.index');
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
