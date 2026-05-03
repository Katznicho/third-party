@extends('layouts.dashboard')

@section('title', 'Client Deductible Ledger')
@section('page-title', 'Client Deductible Ledger')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Client Deductible Ledger</h1>
            <p class="text-slate-600 mt-1">
                {{ $client->full_name }} — co-pay, co-insurance, and deductible applied per authorized visit. Rows come from invoice authorization; the “Ledger posted” column confirms Kashtre recorded the client portion (annual deductible balance updates then).
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clients.usage.deductible', $client) }}" class="px-4 py-2 text-sm border border-amber-200 bg-amber-50 text-amber-900 rounded-lg hover:bg-amber-100">
                ← Annual deductible summary
            </a>
            <a href="{{ route('clients.account-statement', $client) }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm">
                Account statement
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-4">
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Policy Number</label>
                    <input type="text" name="policy_number" value="{{ request('policy_number') }}"
                           class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Search by policy number">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Invoice Number</label>
                    <input type="text" name="invoice_number" value="{{ request('invoice_number') }}"
                           class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Proforma / Kashtre invoice">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Filter
                    </button>
                    @if(request()->hasAny(['policy_number','invoice_number']))
                        <a href="{{ route('clients.deductible-ledger', $client) }}"
                           class="text-xs text-slate-500 hover:text-slate-700">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="px-6 py-4">
            @if ($authorizations->isEmpty())
                <p class="text-sm text-slate-500">No authorized visits found for this client yet. After Kashtre submits an invoice for authorization, co-pay, co-insurance, and deductible amounts appear here.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left">
                        <thead class="bg-slate-50">
                        <tr>
                            <th class="px-2 py-2 font-semibold text-slate-700 whitespace-nowrap">Requested</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 whitespace-nowrap">Policy</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 whitespace-nowrap">Invoice #</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Approved</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Co-pay</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Co-insurance</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Deductible (visit)</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Annual ded. before</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Toward annual ded.</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 text-right whitespace-nowrap">Annual ded. after</th>
                            <th class="px-2 py-2 font-semibold text-slate-700 whitespace-nowrap">Ledger posted</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($authorizations as $auth)
                            @php
                                $b = $auth->breakdown ?? [];
                                $meta = $auth->metadata ?? [];
                                $ledger = $ledgerByAuthId->get($auth->id);
                                $copay = (float) ($b['copay'] ?? 0);
                                $coins = (float) ($b['coinsurance'] ?? 0);
                                $dedVisit = (float) ($b['deductible'] ?? 0);
                                $before = (float) ($meta['deductible_remaining_before'] ?? 0);
                                $toward = (float) ($meta['amount_that_reduces_deductible'] ?? 0);
                                $after = (float) ($meta['deductible_remaining_after'] ?? 0);
                            @endphp
                            <tr class="align-top">
                                <td class="px-2 py-2 text-slate-700 whitespace-nowrap">
                                    {{ $auth->requested_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-2 py-2 text-slate-700 whitespace-nowrap">
                                    {{ $auth->policy?->policy_number ?? '—' }}
                                </td>
                                <td class="px-2 py-2 text-slate-700 whitespace-nowrap">
                                    {{ $auth->external_invoice_number ?? $auth->kashtre_invoice_id ?? '—' }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-700 whitespace-nowrap">
                                    UGX {{ number_format((float) $auth->total_amount, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-800 whitespace-nowrap">
                                    UGX {{ number_format($copay, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-800 whitespace-nowrap">
                                    UGX {{ number_format($coins, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-800 whitespace-nowrap">
                                    UGX {{ number_format($dedVisit, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-700 whitespace-nowrap">
                                    UGX {{ number_format($before, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right font-semibold text-blue-800 whitespace-nowrap">
                                    UGX {{ number_format($toward, 2) }}
                                </td>
                                <td class="px-2 py-2 text-right text-slate-700 whitespace-nowrap">
                                    UGX {{ number_format($after, 2) }}
                                </td>
                                <td class="px-2 py-2 text-slate-700 whitespace-nowrap">
                                    @if($ledger)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-green-50 text-green-800 border border-green-200">Yes</span>
                                        <span class="block text-[10px] text-slate-500 mt-0.5">{{ $ledger->created_at?->format('Y-m-d H:i') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-900 border border-amber-200">Pending client payment</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">
                    <strong>Toward annual ded.</strong> is co-pay / co-insurance (when configured to count) plus deductible slice from this visit, capped by what was left on the policy annual deductible before the visit.
                </p>
                <div class="mt-4">
                    {{ $authorizations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
