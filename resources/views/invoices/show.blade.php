@extends('layouts.dashboard')

@section('title', 'View Invoice')
@section('page-title', 'View Invoice')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Invoice Details</h2>
        <p class="text-slate-600">Invoice detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Invoices</a>
        </div>
    </div>
</div>
@endsection
