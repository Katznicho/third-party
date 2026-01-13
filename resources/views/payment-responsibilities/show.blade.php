@extends('layouts.dashboard')

@section('title', 'View Payment Responsibility')
@section('page-title', 'View Payment Responsibility')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Payment Responsibility Details</h2>
        <p class="text-slate-600">Payment responsibility detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('payment-responsibilities.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Payment Responsibilities</a>
        </div>
    </div>
</div>
@endsection
