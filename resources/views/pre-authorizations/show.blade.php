@extends('layouts.dashboard')

@section('title', 'View Pre-Authorization')
@section('page-title', 'View Pre-Authorization')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Pre-Authorization Details</h2>
        <p class="text-slate-600">Pre-authorization detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('pre-authorizations.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Pre-Authorizations</a>
        </div>
    </div>
</div>
@endsection
