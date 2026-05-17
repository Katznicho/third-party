@extends('layouts.dashboard')

@section('title', 'Payment Pending')
@section('page-title', 'Payment Pending')

@section('content')
@php
    $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];
    $providerName = $meta['provider_name'] ?? ($connection->connected_business_name ?? 'Service provider');
@endphp
<div class="space-y-6 max-w-2xl mx-auto">
    <div>
        <a href="{{ route('connected-companies.financial', $connection->id) }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to financial summary
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Awaiting mobile money confirmation</h1>
        <p class="text-slate-600 mt-1 text-sm">
            Payment to <span class="font-medium">{{ $providerName }}</span> — complete the prompt on the phone, then we will update the provider ledger.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 bg-amber-50 border-b border-amber-100 flex items-start gap-4">
            <div class="shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-amber-900">Status: {{ ucfirst($payment->status) }}</p>
                <p class="text-sm text-amber-800 mt-1">
                    A Yo Payments request was sent to <strong>{{ $payment->mobile_money_number }}</strong>.
                    The provider account in Kashtre is updated only after payment is confirmed.
                </p>
            </div>
        </div>

        <dl class="px-6 py-5 divide-y divide-slate-100 text-sm">
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Reference</dt>
                <dd class="font-mono font-medium text-slate-900">{{ $payment->payment_reference }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Payment to provider</dt>
                <dd class="font-medium text-slate-900">UGX {{ number_format($meta['provider_amount'] ?? 0, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Service charge</dt>
                <dd class="font-medium text-slate-900">UGX {{ number_format($meta['service_charge'] ?? 0, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-900 font-semibold">Total collected</dt>
                <dd class="text-lg font-bold text-blue-700">UGX {{ number_format($payment->amount, 2) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Requested</dt>
                <dd class="text-slate-800">{{ $payment->created_at?->format('M d, Y H:i') }}</dd>
            </div>
        </dl>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row gap-3">
            <form method="POST" action="{{ route('connected-companies.financial.pay.pending.check', [$connection->id, $payment->id]) }}">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">
                    Check payment status
                </button>
            </form>
            <a href="{{ route('connected-companies.financial.pay', $connection->id) }}"
               class="inline-flex justify-center px-6 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-white">
                Start new payment
            </a>
        </div>
    </div>

    <p class="text-xs text-slate-500 text-center">
        Status is also checked automatically every minute. You can leave this page and return later.
    </p>
</div>
@endsection
