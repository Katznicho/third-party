<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesInsurerCompanyLookups;
use App\Models\InsurerSupply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerSupplyController extends Controller
{
    use ManagesInsurerCompanyLookups;

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $supplies = InsurerSupply::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->orderBy('name')
            ->paginate(20);

        return view('insurer-supplies.index', compact('supplies'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('insurer-supplies.create');
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insurer_supplies', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        InsurerSupply::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('supplies.index')->with('success', 'Supply created successfully.');
    }

    public function edit(InsurerSupply $supply)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($supply);

        return view('insurer-supplies.edit', compact('supply'));
    }

    public function update(Request $request, InsurerSupply $supply)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($supply);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insurer_supplies', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($supply->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $supply->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('supplies.index')->with('success', 'Supply updated successfully.');
    }

    public function destroy(InsurerSupply $supply)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($supply);

        $supply->delete();

        return redirect()->route('supplies.index')->with('success', 'Supply deleted successfully.');
    }
}
