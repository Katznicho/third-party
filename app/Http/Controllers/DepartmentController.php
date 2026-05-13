<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    protected function requireInsuranceCompany()
    {
        if (! auth()->user()->insurance_company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be associated with an insurance company to manage departments.');
        }
        

        return null;
    }

    public function index()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $departments = Department::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        return view('departments.create');
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
                Rule::unique('departments', 'name')->where(fn ($q) => $q->where('insurance_company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        Department::create([
            'insurance_company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeDepartment($department);

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeDepartment($department);

        $companyId = auth()->user()->insurance_company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($q) => $q->where('insurance_company_id', $companyId))
                    ->ignore($department->id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $department->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncUserDepartmentNames($department);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($redirect = $this->requireInsuranceCompany()) {
            return $redirect;
        }

        $this->authorizeDepartment($department);

        if ($department->users()->exists()) {
            return redirect()->route('departments.index')
                ->with('error', 'Cannot delete a department that still has users assigned. Reassign those users first.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }

    protected function authorizeDepartment(Department $department): void
    {
        if ((int) $department->insurance_company_id !== (int) auth()->user()->insurance_company_id) {
            abort(403);
        }
    }

    /**
     * Keep legacy users.department text aligned when a structured department is renamed.
     */
    protected function syncUserDepartmentNames(Department $department): void
    {
        $department->users()->update(['department' => $department->name]);
    }
}
