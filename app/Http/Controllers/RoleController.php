<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Traits\AccessTrait;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use AccessTrait;

    public function index()
    {
        $roles = Role::orderBy('name')->paginate(20);
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $roles = $this->getAccessControl();
        $permissions = [];
        return view('roles.create', compact('roles', 'permissions'));
    }

    public function show(Role $role)
    {
        $permissions = $role->permissions ?? [];
        return view('roles.show', compact('role', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions_menu' => 'required|array|min:1',
        ]);

        Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions_menu'],
        ]);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = $role->permissions ?? [];
        $roles = $this->getAccessControl();
        return view('roles.edit', compact('roles', 'permissions', 'role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permissions_menu' => 'required|array|min:1',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions_menu'],
        ]);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}

