@extends('layouts.dashboard')

@section('title', 'View Role')
@section('page-title', 'View Role')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $role->name }}</h1>
            <p class="text-slate-600 mt-1 text-sm">
                {{ $role->description ?: 'No description provided for this role.' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('roles.edit', $role) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Edit Role
            </a>
            <a href="{{ route('roles.index') }}"
               class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm">
                Back to Roles
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Permissions</h2>
        @if(empty($permissions))
            <p class="text-sm text-slate-500">No permissions assigned to this role.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($permissions as $permission)
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                            ✓
                        </span>
                        <span class="text-sm text-slate-700">{{ $permission }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

