@extends('layouts.dashboard')

@section('title', 'Deductible Detail')
@section('page-title', 'Deductible Detail')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('policy-deductible-ledgers.index') }}" class="text-xs text-blue-600 hover:underline">
                ← Back to Deductible Ledger
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Deductible Movement</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        How we arrived at the client and insurer portions for this invoice.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1 text-sm">
                        <p><span class="font-semibold text-slate-700">Policy Number:</span>
                            <span class="text-slate-800">{{ $ledger->policy?->policy_number ?? '—' }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Invoice #:</span>
                            <span class="text-slate-800">{{ $ledger->external_invoice_number ?? $ledger->kashtre_invoice_id ?? '—' }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Date:</span>
                            <span class="text-slate-800">{{ $ledger->created_at?->format('Y-m-d H:i') }}</span>
                        </p>
                    </div>
                    <div class="space-y-1 text-sm">
                        <p><span class="font-semibold text-slate-700">Deductible Before:</span>
                            <span class="text-slate-800">UGX {{ number_format($ledger->deductible_before, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Reduces Deductible (this visit):</span>
                            <span class="text-blue-700 font-semibold">UGX {{ number_format($ledger->amount_that_reduces_deductible, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Deductible After:</span>
                            <span class="text-slate-800">UGX {{ number_format($ledger->deductible_after, 2) }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if($authorization)
            @php
                $breakdown = $authorization->breakdown ?? [];
                $meta = $authorization->metadata ?? [];
            @endphp
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Authorization Breakdown</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        All figures below come from the authorization logic we ran for this invoice.
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p><span class="font-semibold text-slate-700">Approved Amount (invoice total):</span>
                                <span class="text-slate-800">UGX {{ number_format($authorization->total_amount, 2) }}</span>
                            </p>
                            <p><span class="font-semibold text-slate-700">Client Total (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($authorization->client_total, 2) }}</span>
                            </p>
                            <p><span class="font-semibold text-slate-700">Insurer Total (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($authorization->insurance_total, 2) }}</span>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p><span class="font-semibold text-slate-700">Deductible (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($breakdown['deductible'] ?? 0, 2) }}</span>
                            </p>
                            <p><span class="font-semibold text-slate-700">Co-pay (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($breakdown['copay'] ?? 0, 2) }}</span>
                            </p>
                            <p><span class="font-semibold text-slate-700">Co-insurance (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($breakdown['coinsurance'] ?? 0, 2) }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-3 space-y-1">
                        <p><span class="font-semibold text-slate-700">Amount that reduces deductible:</span>
                            <span class="text-blue-700 font-semibold">
                                UGX {{ number_format($meta['amount_that_reduces_deductible'] ?? $ledger->amount_that_reduces_deductible, 2) }}
                            </span>
                        </p>
                        <p class="text-xs text-slate-500">
                            This is calculated as: <strong>Deductible (this visit)</strong>
                            @if(!empty($meta['copay_contributes_to_deductible']))
                                + <strong>Co-pay (this visit)</strong>
                            @endif
                            @if(!empty($meta['coinsurance_contributes_to_deductible']))
                                + <strong>Co-insurance (this visit)</strong>
                            @endif
                            for all amounts configured to contribute to the deductible.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

