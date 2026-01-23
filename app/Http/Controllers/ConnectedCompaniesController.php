<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConnectedCompaniesController extends Controller
{
    /**
     * Display a listing of connected companies.
     */
    public function index()
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }
        
        // Get connections where this insurance company is the main company
        $connections = $insuranceCompany->connectedCompanies()
            ->latest()
            ->get();
        
        return view('connected-companies.index', compact('connections', 'insuranceCompany'));
    }
}
