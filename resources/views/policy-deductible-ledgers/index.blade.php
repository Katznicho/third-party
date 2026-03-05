@extends('layouts.dashboard')

@section('title', 'Deductible Ledger')
@section('page-title', 'Deductible Ledger')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Deductible Ledger</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Track how each invoice has affected the policy deductible over time.
                    </p>
                </div>
            </div>
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
                               placeholder="Search by invoice number">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Filter
                        </button>
                        @if(request()->hasAny(['policy_number','invoice_number']))
                            <a href="{{ route('policy-deductible-ledgers.index') }}"
                               class="ml-3 text-xs text-slate-500 hover:text-slate-700">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="px-6 py-4">
                @if ($ledgers->isEmpty())
                    <p class="text-sm text-slate-500">No deductible movements found yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 font-semibold text-slate-700">Date</th>
                                <th class="px-3 py-2 font-semibold text-slate-700">Policy Number</th>
                                <th class="px-3 py-2 font-semibold text-slate-700">Invoice #</th>
                                <th class="px-3 py-2 font-semibold text-slate-700 text-right">Deductible Before</th>
                                <th class="px-3 py-2 font-semibold text-slate-700 text-right">Reduces Deductible</th>
                                <th class="px-3 py-2 font-semibold text-slate-700 text-right">Deductible After</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach($ledgers as $entry)
                                <tr>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $entry->created_at?->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $entry->policy?->policy_number ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $entry->external_invoice_number ?? $entry->kashtre_invoice_id ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        UGX {{ number_format($entry->deductible_before, 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-blue-700 font-semibold">
                                        UGX {{ number_format($entry->amount_that_reduces_deductible, 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        UGX {{ number_format($entry->deductible_after, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $ledgers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

