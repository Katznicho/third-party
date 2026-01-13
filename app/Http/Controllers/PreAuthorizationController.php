<?php

namespace App\Http\Controllers;

use App\Models\PreAuthorization;
use Illuminate\Http\Request;

class PreAuthorizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $preAuthorizations = PreAuthorization::with(['policy', 'client', 'serviceCategory', 'items'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->latest()
            ->paginate(15);
            
        return view('pre-authorizations.index', compact('preAuthorizations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pre-authorizations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PreAuthorization $preAuthorization)
    {
        return view('pre-authorizations.show', compact('preAuthorization'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PreAuthorization $preAuthorization)
    {
        return view('pre-authorizations.edit', compact('preAuthorization'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PreAuthorization $preAuthorization)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PreAuthorization $preAuthorization)
    {
        //
    }
}
