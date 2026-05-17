@extends('layouts.dashboard')

@section('title', 'Payment Confirmed')
@section('page-title', 'Payment Confirmed')

@section('content')
@php
    $providerName = $receipt['provider_name'] ?? ($connection->connected_business_name ?? 'Service provider');
    $paidAt = !empty($receipt['paid_at']) ? \Carbon\Carbon::parse($receipt['paid_at']) : now();
@endphp
<div class="space-y-6 max-w-2xl mx-auto">
    <div class="text-center pt-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
            <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Payment recorded</h1>
        <p class="text-slate-600 mt-2 text-sm">
            Your payment to <span class="font-medium text-slate-800">{{ $providerName }}</span> has been posted to the provider ledger.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wide">Receipt</h2>
            <span class="text-xs text-slate-500">{{ $paidAt->format('M d, Y H:i') }}</span>
        </div>
        <dl class="px-6 py-5 divide-y divide-slate-100 text-sm">
            <div class="flex justify-between py-3 first:pt-0">
                <dt class="text-slate-500">Reference</dt>
                <dd class="font-mono font-semibold text-slate-900">{{ $receipt['reference'] }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Insurer</dt>
                <dd class="font-medium text-slate-900 text-right">{{ $receipt['insurer_name'] ?? $insuranceCompany->name }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Provider</dt>
                <dd class="font-medium text-slate-900 text-right">{{ $providerName }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Payment method</dt>
                <dd class="font-medium text-slate-900">{{ $receipt['payment_method_label'] ?? $receipt['payment_method'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Amount to provider</dt>
                <dd class="font-medium text-slate-900">UGX {{ number_format($receipt['amount'] ?? 0, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Vendor service charge</dt>
                <dd class="font-medium text-slate-900">UGX {{ number_format($receipt['service_charge'] ?? 0, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-900 font-semibold">Total paid</dt>
                <dd class="text-lg font-bold text-blue-700">UGX {{ number_format($receipt['total_paid'] ?? 0, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">New account balance</dt>
                <dd class="font-semibold {{ ($receipt['new_balance'] ?? 0) < 0 ? 'text-red-600' : 'text-slate-900' }}">
                    UGX {{ number_format(abs($receipt['new_balance'] ?? 0), 2) }}
                    @if(($receipt['new_balance'] ?? 0) < 0)
                        <span class="text-xs font-normal text-red-500">(owed)</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('connected-companies.financial', $connection->id) }}"
           class="inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 shadow-sm">
            View financial summary
        </a>
        <a href="{{ route('connected-companies.show', $connection->id) }}"
           class="inline-flex justify-center items-center px-6 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
            Provider details
        </a>
    </div>
</div>
@endsection
