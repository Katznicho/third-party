@extends('layouts.dashboard')

@section('title', 'Roles')
@section('page-title', 'Roles')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Roles</h1>
            <p class="text-slate-600 mt-1 text-sm">Manage roles and permissions for this insurance company.</p>
        </div>
        <a href="{{ route('roles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            New Role
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($roles->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Permissions count</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($roles as $role)
                            <tr>
                                <td class="px-4 py-3 text-slate-900 font-medium">
                                    {{ $role->name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $role->description ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ is_array($role->permissions) ? count($role->permissions) : 0 }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('roles.show', $role) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $roles->links() }}
            </div>
        @else
            <div class="p-8 text-center text-sm text-slate-500">
                No roles defined yet.
            </div>
        @endif
    </div>
</div>
@endsection

