<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesInsurerCompanyLookups;
use App\Models\InsurerTitle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerTitleController extends Controller
{
    use ManagesInsurerCompanyLookups;

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $titles = InsurerTitle::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('insurer-titles.index', compact('titles'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('insurer-titles.create');
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
                'max:64',
                Rule::unique('insurer_titles', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        InsurerTitle::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('titles.index')->with('success', 'Title created successfully.');
    }

    public function edit(InsurerTitle $title)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($title);

        return view('insurer-titles.edit', ['title' => $title]);
    }

    public function update(Request $request, InsurerTitle $title)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($title);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                Rule::unique('insurer_titles', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($title->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $title->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('titles.index')->with('success', 'Title updated successfully.');
    }

    public function destroy(InsurerTitle $title)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($title);

        if ($title->users()->exists()) {
            return redirect()->route('titles.index')
                ->with('error', 'Cannot delete a title that is assigned to users. Reassign those users first.');
        }

        $title->delete();

        return redirect()->route('titles.index')->with('success', 'Title deleted successfully.');
    }
}
