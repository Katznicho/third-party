<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesInsurerCompanyLookups;
use App\Models\InsurerQualification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerQualificationController extends Controller
{
    use ManagesInsurerCompanyLookups;

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $qualifications = InsurerQualification::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('insurer-qualifications.index', compact('qualifications'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('insurer-qualifications.create');
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
                Rule::unique('insurer_qualifications', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        InsurerQualification::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('qualifications.index')->with('success', 'Qualification created successfully.');
    }

    public function edit(InsurerQualification $qualification)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($qualification);

        return view('insurer-qualifications.edit', compact('qualification'));
    }

    public function update(Request $request, InsurerQualification $qualification)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($qualification);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insurer_qualifications', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($qualification->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $qualification->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('qualifications.index')->with('success', 'Qualification updated successfully.');
    }

    public function destroy(InsurerQualification $qualification)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeCompanyRecord($qualification);

        if ($qualification->users()->exists()) {
            return redirect()->route('qualifications.index')
                ->with('error', 'Cannot delete a qualification that is assigned to users. Reassign those users first.');
        }

        $qualification->delete();

        return redirect()->route('qualifications.index')->with('success', 'Qualification deleted successfully.');
    }
}
