@extends('layouts.dashboard')

@section('title', 'View Client')
@section('page-title', 'View Client')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Client Details</h2>
        <p class="text-slate-600">Client detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('clients.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Clients</a>
        </div>
    </div>
</div>
@endsection
