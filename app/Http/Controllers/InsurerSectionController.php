<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesInsurerCompanyLookups;
use App\Models\InsurerSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerSectionController extends Controller
{
    use ManagesInsurerCompanyLookups;

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $sections = InsurerSection::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('insurer-sections.index', compact('sections'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('insurer-sections.create');
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
                Rule::unique('insurer_sections', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        InsurerSection::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(InsurerSection $section)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($section);

        return view('insurer-sections.edit', compact('section'));
    }

    public function update(Request $request, InsurerSection $section)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($section);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insurer_sections', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($section->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $section->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(InsurerSection $section)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($section);

        if ($section->users()->exists()) {
            return redirect()->route('sections.index')
                ->with('error', 'Cannot delete a section that still has users assigned. Reassign those users first.');
        }

        $section->delete();

        return redirect()->route('sections.index')->with('success', 'Section deleted successfully.');
    }
}
