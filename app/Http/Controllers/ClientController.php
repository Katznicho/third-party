<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Get all clients (both with and without policies for this insurance company)
        // For now, show all clients, but filter policies by insurance company
        $clients = Client::with(['policies' => function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            }, 'principalMember', 'plan'])
            ->latest()
            ->paginate(15);
            
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:principal,dependent',
            'principal_member_id' => 'required_if:type,dependent|nullable|exists:clients,id',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:50',
            'id_passport_no' => 'required|string|max:255|unique:clients,id_passport_no',
            'gender' => 'nullable|in:Male,Female',
            'tin' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'employer_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'home_physical_address' => 'nullable|string|max:500',
            'office_physical_address' => 'nullable|string|max:500',
            'home_telephone' => 'nullable|string|max:50',
            'office_telephone' => 'nullable|string|max:50',
            'cell_phone' => 'nullable|string|max:50',
            'whatsapp_line' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'relation_to_principal' => 'nullable|string|max:255',
            'next_of_kin_surname' => 'nullable|string|max:255',
            'next_of_kin_first_name' => 'nullable|string|max:255',
            'next_of_kin_other_names' => 'nullable|string|max:255',
            'next_of_kin_title' => 'nullable|string|max:50',
            'next_of_kin_relation' => 'nullable|string|max:255',
            'next_of_kin_id_passport_no' => 'nullable|string|max:255',
            'next_of_kin_cell_phone' => 'nullable|string|max:50',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_post_address' => 'nullable|string|max:500',
            'next_of_kin_physical_address' => 'nullable|string|max:500',
            'has_deductible' => 'nullable|boolean',
            'deductible_amount' => 'nullable|numeric|min:0',
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

        $client = Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $client->load(['principalMember', 'dependents', 'policies.insuranceCompany']);
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $principals = Client::where('type', 'principal')
            ->where('id', '!=', $client->id)
            ->get();
        return view('clients.edit', compact('client', 'principals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'type' => 'required|in:principal,dependent',
            'principal_member_id' => 'required_if:type,dependent|nullable|exists:clients,id',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:50',
            'id_passport_no' => 'required|string|max:255|unique:clients,id_passport_no,' . $client->id,
            'gender' => 'nullable|in:Male,Female',
            'tin' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'employer_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'home_physical_address' => 'nullable|string|max:500',
            'office_physical_address' => 'nullable|string|max:500',
            'home_telephone' => 'nullable|string|max:50',
            'office_telephone' => 'nullable|string|max:50',
            'cell_phone' => 'nullable|string|max:50',
            'whatsapp_line' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'relation_to_principal' => 'nullable|string|max:255',
            'next_of_kin_surname' => 'nullable|string|max:255',
            'next_of_kin_first_name' => 'nullable|string|max:255',
            'next_of_kin_other_names' => 'nullable|string|max:255',
            'next_of_kin_title' => 'nullable|string|max:50',
            'next_of_kin_relation' => 'nullable|string|max:255',
            'next_of_kin_id_passport_no' => 'nullable|string|max:255',
            'next_of_kin_cell_phone' => 'nullable|string|max:50',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_post_address' => 'nullable|string|max:500',
            'next_of_kin_physical_address' => 'nullable|string|max:500',
            'has_deductible' => 'nullable|boolean',
            'deductible_amount' => 'nullable|numeric|min:0',
            'telemedicine_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        // Handle checkboxes
        $validated['has_deductible'] = $request->boolean('has_deductible');
        $validated['telemedicine_only'] = $request->boolean('telemedicine_only');
        $validated['is_active'] = $request->boolean('is_active');

        // Set principal_member_id to null if not dependent
        if ($validated['type'] === 'principal') {
            $validated['principal_member_id'] = null;
            $validated['relation_to_principal'] = null;
        }

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        // Check if client has policies
        if ($client->policies()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete client with existing policies. Please remove policies first.');
        }

        // Check if client has dependents
        if ($client->dependents()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete principal member with dependents. Please remove dependents first.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
