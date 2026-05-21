<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesInsurerCompanyLookups;
use App\Models\InsurerStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerStoreController extends Controller
{
    use ManagesInsurerCompanyLookups;

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $stores = InsurerStore::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->orderBy('name')
            ->paginate(20);

        return view('insurer-stores.index', compact('stores'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('insurer-stores.create');
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
                Rule::unique('insurer_stores', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        InsurerStore::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(InsurerStore $store)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($store);

        return view('insurer-stores.edit', compact('store'));
    }

    public function update(Request $request, InsurerStore $store)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($store);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insurer_stores', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($store->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $store->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(InsurerStore $store)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($store);

        $store->delete();

        return redirect()->route('stores.index')->with('success', 'Store deleted successfully.');
    }
}
