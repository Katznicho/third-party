<?php

namespace App\Http\Controllers;

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
            'name',
            'surname',
            'first_name',
            'middle_name',
            'national_id',
            'department',
            'gender',
            'birth_date',
            'marital_status',
        ] as $key) {
            if ($request->input($key) === '') {
                $request->merge([$key => null]);
            }
        }
    }

    /**
     * Display name: optional explicit "name", else name parts, else email local-part, else "User".
     */
    protected function resolveDisplayName(Request $request): string
    {
        $explicit = trim((string) $request->input('name', ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $parts = trim(implode(' ', array_filter([
            trim((string) $request->input('surname', '')),
            trim((string) $request->input('first_name', '')),
            trim((string) $request->input('middle_name', '')),
        ], fn ($s) => $s !== '')));

        if ($parts !== '') {
            return $parts;
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

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->normalizeNullableEnrollment($request);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed,separated,other',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $displayName = $this->resolveDisplayName($request);

        $user = User::create([
            'name' => $displayName,
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'department' => $validated['department'] ?? null,
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

        return view('users.edit', compact('user'));
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

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed,separated,other',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $displayName = $this->resolveDisplayName($request);

        $user->update([
            'name' => $displayName,
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'department' => $validated['department'] ?? null,
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
