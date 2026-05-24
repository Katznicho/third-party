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

    @if(!$historyError)
        @php
            $sv = $statementView ?? 'items';
            $rows = $history['rows'] ?? [];
            $pg = $history['pagination'] ?? [];
            $lastPage = (int) ($pg['last_page'] ?? 1);
            $total = (int) ($pg['total'] ?? 0);
            $invoices = $ledgerInvoices ?? [];
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-start mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $sv === 'items' ? 'Statement by item' : 'Invoices' }}
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        @if($sv === 'items')
                            Each debit is split across invoice line items when Kashtre includes item rows.
                        @else
                            All insurance invoices for your account at this provider.
                        @endif
                    </p>
                </div>
                <div class="flex rounded-lg border border-slate-200 p-1 bg-slate-50 shrink-0">
                    <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'items', 'page' => $page]) }}"
                       class="px-4 py-2 text-sm font-medium rounded-md {{ $sv === 'items' ? 'bg-white shadow text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">
                        Items
                    </a>
                    <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'invoices']) }}"
                       class="px-4 py-2 text-sm font-medium rounded-md {{ $sv === 'invoices' ? 'bg-white shadow text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">
                        Invoices
                    </a>
                </div>
            </div>

            @if($sv === 'items')
                <p class="text-sm text-slate-500 mb-4">
                    Ledger entries {{ count($rows) }} on this page of {{ $total }} total;
                    {{ isset($itemStatementRows) ? $itemStatementRows->count() : 0 }} line{{ (isset($itemStatementRows) ? $itemStatementRows->count() : 0) === 1 ? '' : 's' }} displayed (debits expanded per item).
                </p>
            @else
                <p class="text-sm text-slate-500 mb-4">{{ count($invoices) }} invoice{{ count($invoices) === 1 ? '' : 's' }}</p>
            @endif

            @if($sv === 'items' && isset($itemStatementRows) && $itemStatementRows->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Item / line</th>
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
                            @foreach($itemStatementRows as $row)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ !empty($row['created_at']) ? \Carbon\Carbon::parse($row['created_at'])->format('Y-m-d H:i:s') : '—' }}</td>
                                    <td class="px-4 py-3 text-slate-800">
                                        <span class="font-medium">{{ $row['line_label'] ?? '—' }}</span>
                                        @if(!empty($row['detail_description']) && ($row['detail_description'] ?? '') !== ($row['line_label'] ?? ''))
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $row['detail_description'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        @if(!empty($row['client']))
                                            <span class="font-medium">{{ $row['client']['name'] ?? '' }}</span>
                                            @if(!empty($row['client']['client_id']))
                                                <br><span class="text-xs text-slate-500">ID: {{ $row['client']['client_id'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ data_get($row, 'invoice.invoice_number', 'N/A') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ ($row['transaction_type'] ?? '') === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($row['transaction_type'] ?? '') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium {{ ($row['transaction_type'] ?? '') === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ ($row['transaction_type'] ?? '') === 'credit' ? '+' : '-' }}{{ number_format((float) ($row['amount'] ?? 0), 2) }} UGX
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        @if($row['new_balance'] !== null)
                                            {{ number_format((float) $row['new_balance'], 2) }} UGX
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ !empty($row['payment_method']) ? ucwords(str_replace('_', ' ', $row['payment_method'])) : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if(!empty($row['payment_status']))
                                            <span class="px-2 py-1 text-xs rounded-full {{ $row['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : ($row['payment_status'] === 'pending_payment' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst(str_replace('_', ' ', $row['payment_status'])) }}
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
            @elseif($sv === 'invoices' && count($invoices) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Paid</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Balance due</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoices as $invoice)
                                @php
                                    $payStatus = $invoice['payment_status'] ?? 'pending_payment';
                                    $statusClass = match ($payStatus) {
                                        'paid' => 'bg-green-100 text-green-800',
                                        'partial' => 'bg-blue-100 text-blue-800',
                                        'pending_payment' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        {{ !empty($invoice['created_at']) ? \Carbon\Carbon::parse($invoice['created_at'])->format('Y-m-d H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-900">{{ $invoice['invoice_number'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        <span class="font-medium">{{ $invoice['client_name'] ?? 'N/A' }}</span>
                                        @if(!empty($invoice['client_id']))
                                            <br><span class="text-xs text-slate-500">ID: {{ $invoice['client_id'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">UGX {{ number_format((float) ($invoice['total_amount'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-green-700">UGX {{ number_format((float) ($invoice['amount_paid'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium {{ ($invoice['balance_due'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-800' }}">
                                        UGX {{ number_format((float) ($invoice['balance_due'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $payStatus)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-slate-500 text-center py-8">
                    {{ $sv === 'invoices' ? 'No invoices yet for this provider.' : 'No ledger entries.' }}
                </p>
            @endif

            @if($sv === 'items' && $lastPage > 1 && isset($itemStatementRows) && $itemStatementRows->count() > 0)
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div class="text-slate-600">Page {{ $page }} of {{ $lastPage }}</div>
                    <div class="flex gap-2">
                        @if($page > 1)
                            <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'page' => $page - 1, 'view' => $sv]) }}" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50">Previous</a>
                        @endif
                        @if($page < $lastPage)
                            <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'page' => $page + 1, 'view' => $sv]) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Next</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
