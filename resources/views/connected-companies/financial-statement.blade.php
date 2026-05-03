@extends('layouts.dashboard')

@section('title', 'Balance Statement')
@section('page-title', 'Balance Statement')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('connected-companies.financial', $connection->id) }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Financial Summary
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Full Statement</h1>
            <p class="text-slate-600 mt-1 text-sm">
                {{ $insuranceCompany->name }} — {{ $connection->connected_business_name ?? 'Service provider' }}
            </p>
        </div>
    </div>

    @if($historyError)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $historyError }}
        </div>
    @endif

    @if($history && !$historyError)
        @php
            $rows = $history['rows'] ?? [];
            $pg = $history['pagination'] ?? [];
            $lastPage = (int) ($pg['last_page'] ?? 1);
            $total = (int) ($pg['total'] ?? 0);
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500 mb-4">Showing {{ count($rows) }} of {{ $total }} entries</p>

            @if(count($rows) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Payment Method</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($rows as $h)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ $h['created_at'] ? \Carbon\Carbon::parse($h['created_at'])->format('Y-m-d H:i:s') : '—' }}</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $h['description'] ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        @if(!empty($h['client']))
                                            <span class="font-medium">{{ $h['client']['name'] ?? '' }}</span>
                                            @if(!empty($h['client']['client_id']))
                                                <br><span class="text-xs text-slate-500">ID: {{ $h['client']['client_id'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ data_get($h, 'invoice.invoice_number', 'N/A') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ ($h['transaction_type'] ?? '') === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($h['transaction_type'] ?? '') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium {{ ($h['transaction_type'] ?? '') === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ ($h['transaction_type'] ?? '') === 'credit' ? '+' : '-' }}{{ number_format(abs($h['change_amount'] ?? 0), 2) }} UGX
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ number_format($h['new_balance'] ?? 0, 2) }} UGX</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ $h['payment_method'] ? ucwords(str_replace('_', ' ', $h['payment_method'])) : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if(!empty($h['payment_status']))
                                            <span class="px-2 py-1 text-xs rounded-full {{ $h['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : ($h['payment_status'] === 'pending_payment' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst(str_replace('_', ' ', $h['payment_status'])) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($lastPage > 1)
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div class="text-slate-600">Page {{ $page }} of {{ $lastPage }}</div>
                        <div class="flex gap-2">
                            @if($page > 1)
                                <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'page' => $page - 1]) }}" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50">Previous</a>
                            @endif
                            @if($page < $lastPage)
                                <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'page' => $page + 1]) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Next</a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <p class="text-sm text-slate-500 text-center py-8">No ledger entries.</p>
            @endif
        </div>
    @endif
</div>
@endsection
