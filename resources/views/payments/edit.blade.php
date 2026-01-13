@extends('layouts.dashboard')

@section('title', 'Edit Payment')
@section('page-title', 'Edit Payment')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Edit Payment</h2>
        <p class="text-slate-600">Payment edit form coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('payments.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Payments</a>
        </div>
    </div>
</div>
@endsection
