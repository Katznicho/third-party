@extends('layouts.dashboard')

@section('title', 'Create Invoice')
@section('page-title', 'Create Invoice')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Create New Invoice</h2>
        <p class="text-slate-600">Invoice creation form coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Invoices</a>
        </div>
    </div>
</div>
@endsection
