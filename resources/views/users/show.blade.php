@extends('layouts.dashboard')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">User Details</h1>
            <p class="text-slate-600 mt-1">View user information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('users.edit', $user) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Edit User
            </a>
            <a href="{{ route('users.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
                ← Back to Users
            </a>
        </div>
    </div>

    <!-- User Details -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Full Name</label>
                <p class="text-lg font-semibold text-slate-900">{{ $user->name }}</p>
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Username</label>
                <p class="text-lg font-semibold text-slate-900">{{ $user->username }}</p>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Email</label>
                <p class="text-lg font-semibold text-slate-900">{{ $user->email }}</p>
            </div>

            <!-- Insurance Company -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Insurance Company</label>
                <p class="text-lg font-semibold text-slate-900">
                    {{ $user->insuranceCompany->name ?? 'N/A' }}
                </p>
            </div>

            <!-- Created At -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Created At</label>
                <p class="text-lg font-semibold text-slate-900">{{ $user->created_at->format('F d, Y \a\t h:i A') }}</p>
            </div>

            <!-- Updated At -->
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Last Updated</label>
                <p class="text-lg font-semibold text-slate-900">{{ $user->updated_at->format('F d, Y \a\t h:i A') }}</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end space-x-4">
            <a href="{{ route('users.edit', $user) }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Edit User
            </a>
            @if($user->id !== auth()->id())
                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-150">
                        Delete User
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
