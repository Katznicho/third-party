@extends('layouts.dashboard')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Role</h1>
            <p class="text-slate-600 mt-1 text-sm">Update role details and permissions.</p>
        </div>
        <a href="{{ route('roles.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm">
            ← Back to Roles
        </a>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}"
                       class="mt-1 block w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <input type="text" name="description" value="{{ old('description', $role->description) }}"
                       class="mt-1 block w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">
                Permissions <span class="text-red-500">*</span>
            </label>

            @php
                $selected = old('permissions_menu', $permissions ?? []);
            @endphp

            <div class="mt-3 space-y-4">
                @foreach ($roles as $group => $categories)
                    <div class="border border-slate-200 rounded-lg p-4">
                        <label class="inline-flex items-center mb-2">
                            <input type="checkbox" name="permissions_menu[]" value="{{ $group }}"
                                   class="module-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                   {{ in_array($group, $selected) ? 'checked' : '' }}>
                            <span class="ml-2 font-semibold text-slate-800">{{ $group }}</span>
                        </label>

                        @foreach ($categories as $category => $perms)
                            <div class="pl-5 mt-2 border-l border-slate-200">
                                <label class="inline-flex items-center mb-1">
                                    <input type="checkbox" name="permissions_menu[]" value="{{ $category }}"
                                           class="submodule-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                           {{ in_array($category, $selected) ? 'checked' : '' }}>
                                    <span class="ml-2 font-medium text-slate-700">{{ $category }}</span>
                                </label>
                                <div class="mt-1 ml-4 space-y-1">
                                    @foreach ($perms as $permission)
                                        <label class="inline-flex items-center space-x-2 mr-4">
                                            <input type="checkbox" name="permissions_menu[]" value="{{ $permission }}"
                                                   class="action-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                   {{ in_array($permission, $selected) ? 'checked' : '' }}>
                                            <span class="text-sm text-slate-700">{{ $permission }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @error('permissions_menu')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('roles.index') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                Update Role
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.module-checkbox').forEach(function (moduleCheckbox) {
                moduleCheckbox.addEventListener('change', function () {
                    const container = moduleCheckbox.closest('.border');
                    container.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                        cb.checked = moduleCheckbox.checked;
                    });
                });
            });
        });
    </script>
</div>
@endsection

