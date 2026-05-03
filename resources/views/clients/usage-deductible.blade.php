@extends('layouts.dashboard')

@section('title', 'Deductible ledger')
@section('page-title', 'Deductible ledger')

@section('content')
@php
    $ledgerStatement = $ledgerStatement ?? collect();
    $policiesWithDeductible = $policiesWithDeductible ?? collect();
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Deductible ledger statement</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $client->full_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">Export PDF</a>
            <a href="{{ route('clients.account-statement', $client) }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">Account statement</a>
        </div>
    </div>

    @if($policiesWithDeductible->isNotEmpty())
        <div class="space-y-6">
            @foreach($policiesWithDeductible as $policy)
                @php
                    $ledgerForPolicy = $ledgerStatement->where('policy_id', $policy->id);
                    $lastRow = $ledgerForPolicy->sortByDesc('created_at')->first();
                    $annual = (float) ($policy->deductible_amount ?? 0);
                    $remaining = $lastRow !== null ? (float) $lastRow->deductible_after : $annual;
                    $remaining = max(0, $remaining);
                    $used = max(0, $annual - $remaining);
                    $pctUsed = $annual > 0 ? min(100, round(($used / $annual) * 100, 1)) : 0;
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-4">Policy {{ $policy->policy_number ?? '—' }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Annual limit</p>
                            <p class="text-2xl font-bold text-slate-900 tabular-nums mt-1">UGX {{ number_format($annual, 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-xs font-medium text-amber-800 uppercase tracking-wide">Used</p>
                            <p class="text-2xl font-bold text-amber-900 tabular-nums mt-1">UGX {{ number_format($used, 2) }}</p>
                            <p class="text-[11px] text-amber-700/80 mt-1">From posted ledger entries</p>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-medium text-emerald-800 uppercase tracking-wide">Remaining</p>
                            <p class="text-2xl font-bold text-emerald-900 tabular-nums mt-1">UGX {{ number_format($remaining, 2) }}</p>
                            <p class="text-[11px] text-emerald-700/80 mt-1">Left this policy year</p>
                        </div>
                    </div>
                    @if($annual > 0)
                        <div class="mt-5">
                            <div class="flex justify-between text-xs text-slate-500 mb-1">
                                <span>Progress</span>
                                <span class="font-medium text-slate-700">{{ $pctUsed }}% used</span>
                            </div>
                            <div class="h-3 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all" style="width: {{ $pctUsed }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500 border border-slate-200 rounded-lg px-4 py-3 bg-slate-50">No policy with an annual deductible is linked to this client.</p>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-800">Movements</h2>
            <p class="text-xs text-slate-500 mt-0.5">Each row reduces <strong>Remaining</strong> above after Kashtre confirms client payment.</p>
        </div>
        <div class="overflow-x-auto">
            @if($ledgerStatement->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-slate-500">No movements recorded yet — <strong>Used</strong> stays at UGX 0.00 until payments are posted.</p>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Policy</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Before</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Applied</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">After</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($ledgerStatement as $entry)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $entry->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $entry->policy?->policy_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $entry->external_invoice_number ?? $entry->kashtre_invoice_id ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">UGX {{ number_format((float) $entry->deductible_before, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-900 font-medium">UGX {{ number_format((float) $entry->amount_that_reduces_deductible, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-900">UGX {{ number_format((float) $entry->deductible_after, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <p class="text-xs text-slate-500">
        <a href="{{ route('clients.deductible-ledger', $client) }}" class="text-blue-600 hover:underline">Invoice-level breakdown</a>
        — co-pay, co-insurance, per-visit deductible on each bill
    </p>
</div>
@endsection
