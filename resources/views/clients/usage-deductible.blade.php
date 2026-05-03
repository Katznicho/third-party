@extends('layouts.dashboard')

@section('title', 'Deductible Usage')
@section('page-title', 'Deductible Usage')

@section('content')
@php
    $ledgerStatement = $ledgerStatement ?? collect();
    $policiesWithDeductible = $policiesWithDeductible ?? collect();
@endphp
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Deductible Usage</h1>
            <p class="text-slate-600 mt-1">
                {{ $client->full_name }} — Total deductible applied on authorized visits:
                <span class="font-semibold">UGX {{ number_format($totalMetric, 2) }}</span>
            </p>
            <p class="text-xs text-slate-500 mt-2 max-w-2xl">
                The <strong>statement</strong> below shows how the annual deductible balance moves (opening → each reduction → remaining).
                Visit amounts in the second table come from invoice authorization breakdowns. Ledger lines are recorded when a client-portion payment completes (same source as the company deductible ledger).
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('clients.deductible-ledger', $client) }}" class="px-4 py-2 text-sm border border-blue-200 bg-blue-50 text-blue-800 rounded-lg hover:bg-blue-100">
                Detailed ledger (filters)
            </a>
            <a href="{{ route('clients.account-statement', $client) }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm">
                ← Account statement
            </a>
        </div>
    </div>

    @if($policiesWithDeductible->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($policiesWithDeductible as $policy)
                @php
                    $ledgerForPolicy = $ledgerStatement->where('policy_id', $policy->id);
                    $lastRow = $ledgerForPolicy->sortByDesc('created_at')->first();
                    $annual = (float) ($policy->deductible_amount ?? 0);
                    $remaining = $lastRow !== null ? (float) $lastRow->deductible_after : $annual;
                    $usedFromLedger = $annual - $remaining;
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Policy {{ $policy->policy_number ?? '—' }}</p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-600">Annual deductible</dt>
                            <dd class="font-medium text-slate-900">UGX {{ number_format($annual, 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-600">Applied (from ledger)</dt>
                            <dd class="text-amber-800 font-medium">UGX {{ number_format(max(0, $usedFromLedger), 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 pt-2 border-t border-slate-100">
                            <dt class="text-slate-700 font-semibold">Remaining</dt>
                            <dd class="font-semibold text-slate-900">UGX {{ number_format(max(0, $remaining), 2) }}</dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-800">Deductible balance statement</h2>
            <p class="text-xs text-slate-500 mt-0.5">Chronological movements — balance before, amount applied, balance after.</p>
        </div>
        <div class="overflow-x-auto">
            @if($ledgerStatement->isEmpty())
                <div class="px-4 py-8 text-center">
                    <p class="text-sm text-slate-600 mb-2">No ledger movements recorded yet for this client.</p>
                    @if($policiesWithDeductible->isNotEmpty())
                        <p class="text-xs text-slate-500 max-w-lg mx-auto">
                            Annual deductible is set on the policy (see summary cards above). Rows appear here after eligible visits reduce the deductible — typically when the client-portion payment is completed and we persist a ledger entry.
                        </p>
                    @else
                        <p class="text-xs text-slate-500">This client has no policy with an annual deductible configured.</p>
                    @endif
                </div>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Policy</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Balance before</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Applied</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Balance after</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($ledgerStatement as $entry)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $entry->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $entry->policy?->policy_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $entry->external_invoice_number ?? $entry->kashtre_invoice_id ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">UGX {{ number_format((float) $entry->deductible_before, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-amber-800 font-medium">UGX {{ number_format((float) $entry->amount_that_reduces_deductible, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-900 font-medium">UGX {{ number_format((float) $entry->deductible_after, 2) }}</td>
                                <td class="px-4 py-3 text-slate-600 text-xs">{{ $entry->change_type ?? '—' }}</td>
                            </tr>
                            @if($entry->notes)
                                <tr class="bg-slate-50/80">
                                    <td colspan="7" class="px-4 py-2 text-xs text-slate-500">{{ $entry->notes }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-b border-slate-200 bg-slate-50">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Deductible by visit (authorization)</h2>
                <p class="text-xs text-slate-500">Per-invoice deductible from the authorization breakdown (may differ from ledger timing).</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by invoice #..."
                        class="w-56 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button type="submit" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ request()->url() }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
                    @endif
                </form>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100 whitespace-nowrap">
                    Export PDF
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Requested</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Deductible (UGX)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($authorizations as $auth)
                        @php $breakdown = $auth->breakdown ?? []; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $auth->requested_at?->format('d M Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-800">{{ $auth->external_invoice_number ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-amber-700">{{ number_format($breakdown['deductible'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">
                                No deductible usage found for this client.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($authorizations instanceof \Illuminate\Pagination\LengthAwarePaginator && $authorizations->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $authorizations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
