@extends('layouts.dashboard')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit User</h1>
            <p class="text-slate-600 mt-1">Username and email are required. Other fields are optional.</p>
        </div>
        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Users
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-2">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('username') border-red-500 @enderror">
                    @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="role_id" class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                <select name="role_id" id="role_id"
                    class="w-full max-w-md px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('role_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ (string) old('role_id', $user->role_id) === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                        New password <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="password" name="password" id="password" minlength="8" placeholder="Leave blank to keep current"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" minlength="8" placeholder="Confirm new password"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            @include('users.partials.optional-fields', ['user' => $user])

            <div class="flex justify-end space-x-4 pt-4 border-t border-slate-200">
                <a href="{{ route('users.index') }}" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition duration-150">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">Update User</button>
            </div>
        </form>
    </div>
</div>
@endsection
