<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InsurerQualification;
use App\Models\InsurerSection;
use App\Models\InsurerTitle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Optional enrollment fields: empty strings become null before validation/persistence.
     */
    protected function normalizeNullableEnrollment(Request $request): void
    {
        foreach ([
            'surname',
            'first_name',
            'middle_name',
            'national_id',
            'gender',
            'birth_date',
            'marital_status',
        ] as $key) {
            if ($request->input($key) === '') {
                $request->merge([$key => null]);
            }
        }
        if ($request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }
        if ($request->input('title_id') === '') {
            $request->merge(['title_id' => null]);
        }
        if ($request->input('qualification_id') === '') {
            $request->merge(['qualification_id' => null]);
        }
        if ($request->input('section_id') === '') {
            $request->merge(['section_id' => null]);
        }
    }

    protected function resolveSectionId(?int $sectionId): ?int
    {
        $companyId = auth()->user()->insurance_company_id;
        if (! $sectionId || ! $companyId) {
            return null;
        }

        return InsurerSection::query()
            ->where('insurance_company_id', $companyId)
            ->where('id', $sectionId)
            ->exists() ? $sectionId : null;
    }

    protected function resolveTitleId(?int $titleId): ?int
    {
        $companyId = auth()->user()->insurance_company_id;
        if (! $titleId || ! $companyId) {
            return null;
        }

        return InsurerTitle::query()
            ->where('insurance_company_id', $companyId)
            ->where('id', $titleId)
            ->exists() ? $titleId : null;
    }

    protected function resolveQualificationId(?int $qualificationId): ?int
    {
        $companyId = auth()->user()->insurance_company_id;
        if (! $qualificationId || ! $companyId) {
            return null;
        }

        return InsurerQualification::query()
            ->where('insurance_company_id', $companyId)
            ->where('id', $qualificationId)
            ->exists() ? $qualificationId : null;
    }

    /**
     * @return array{department_id: ?int, department: ?string}
     */
    protected function resolveDepartmentAssignment(?int $departmentId): array
    {
        $companyId = auth()->user()->insurance_company_id;
        if (! $departmentId || ! $companyId) {
            return ['department_id' => null, 'department' => null];
        }

        $name = Department::query()
            ->where('insurance_company_id', $companyId)
            ->where('id', $departmentId)
            ->value('name');

        if ($name === null) {
            return ['department_id' => null, 'department' => null];
        }

        return ['department_id' => $departmentId, 'department' => $name];
    }

    /**
     * Stored display name: surname when present, otherwise the email local-part, otherwise "User".
     */
    protected function resolveDisplayName(Request $request): string
    {
        $surname = trim((string) $request->input('surname', ''));
        if ($surname !== '') {
            return $surname;
        }

        $email = trim((string) $request->input('email', ''));
        if ($email !== '') {
            $local = explode('@', $email)[0];

            return $local !== '' ? $local : 'User';
        }

        return 'User';
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;

        $users = User::where('insurance_company_id', $insuranceCompanyId)
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $companyId = auth()->user()->insurance_company_id;

        $departments = Department::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $titles = InsurerTitle::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $qualifications = InsurerQualification::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $sections = InsurerSection::where('insurance_company_id', $companyId)->orderBy('name')->get();

        return view('users.create', compact('roles', 'departments', 'titles', 'qualifications', 'sections'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->normalizeNullableEnrollment($request);

        $companyId = auth()->user()->insurance_company_id;

        $deptRule = Rule::exists('departments', 'id')->where('insurance_company_id', $companyId);
        $titleRule = Rule::exists('insurer_titles', 'id')->where('insurance_company_id', $companyId);
        $qualRule = Rule::exists('insurer_qualifications', 'id')->where('insurance_company_id', $companyId);
        $sectionRule = Rule::exists('insurer_sections', 'id')->where('insurance_company_id', $companyId);

        $validated = $request->validate([
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'department_id' => ['nullable', 'integer', $deptRule],
            'title_id' => ['nullable', 'integer', $titleRule],
            'qualification_id' => ['nullable', 'integer', $qualRule],
            'section_id' => ['nullable', 'integer', $sectionRule],
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed,separated,other',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $displayName = $this->resolveDisplayName($request);
        $deptFields = $this->resolveDepartmentAssignment(isset($validated['department_id']) ? (int) $validated['department_id'] : null);
        $titleId = $this->resolveTitleId(isset($validated['title_id']) ? (int) $validated['title_id'] : null);
        $qualificationId = $this->resolveQualificationId(isset($validated['qualification_id']) ? (int) $validated['qualification_id'] : null);
        $sectionId = $this->resolveSectionId(isset($validated['section_id']) ? (int) $validated['section_id'] : null);

        $user = User::create([
            'name' => $displayName,
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'title_id' => $titleId,
            'qualification_id' => $qualificationId,
            'section_id' => $sectionId,
            'department_id' => $deptFields['department_id'],
            'department' => $deptFields['department'],
            'gender' => $validated['gender'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'],
            // Required DB field; user will replace this via reset-password flow.
            'password' => Hash::make(Str::random(32)),
            'insurance_company_id' => auth()->user()->insurance_company_id,
            'role_id' => $validated['role_id'] ?? null,
        ]);

        // Send password setup email
        $token = app('auth.password.broker')->createToken($user);
        $user->sendPasswordResetNotification($token);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully and password setup email sent.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Ensure user belongs to the same insurance company
        if ($user->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access.');
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Ensure user belongs to the same insurance company
        if ($user->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = auth()->user()->insurance_company_id;

        $departments = Department::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $titles = InsurerTitle::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $qualifications = InsurerQualification::where('insurance_company_id', $companyId)->orderBy('name')->get();
        $sections = InsurerSection::where('insurance_company_id', $companyId)->orderBy('name')->get();

        return view('users.edit', compact('user', 'departments', 'titles', 'qualifications', 'sections'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Ensure user belongs to the same insurance company
        if ($user->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access.');
        }

        $this->normalizeNullableEnrollment($request);

        $companyId = auth()->user()->insurance_company_id;

        $deptRule = Rule::exists('departments', 'id')->where('insurance_company_id', $companyId);
        $titleRule = Rule::exists('insurer_titles', 'id')->where('insurance_company_id', $companyId);
        $qualRule = Rule::exists('insurer_qualifications', 'id')->where('insurance_company_id', $companyId);
        $sectionRule = Rule::exists('insurer_sections', 'id')->where('insurance_company_id', $companyId);

        $validated = $request->validate([
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'department_id' => ['nullable', 'integer', $deptRule],
            'title_id' => ['nullable', 'integer', $titleRule],
            'qualification_id' => ['nullable', 'integer', $qualRule],
            'section_id' => ['nullable', 'integer', $sectionRule],
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed,separated,other',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $displayName = $this->resolveDisplayName($request);
        $deptFields = $this->resolveDepartmentAssignment(isset($validated['department_id']) ? (int) $validated['department_id'] : null);
        $titleId = $this->resolveTitleId(isset($validated['title_id']) ? (int) $validated['title_id'] : null);
        $qualificationId = $this->resolveQualificationId(isset($validated['qualification_id']) ? (int) $validated['qualification_id'] : null);
        $sectionId = $this->resolveSectionId(isset($validated['section_id']) ? (int) $validated['section_id'] : null);

        $user->update([
            'name' => $displayName,
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'title_id' => $titleId,
            'qualification_id' => $qualificationId,
            'section_id' => $sectionId,
            'department_id' => $deptFields['department_id'],
            'department' => $deptFields['department'],
            'gender' => $validated['gender'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Ensure user belongs to the same insurance company
        if ($user->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }
}
