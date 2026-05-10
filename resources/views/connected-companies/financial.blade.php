@extends('layouts.dashboard')

@section('title', 'Financial Summary')
@section('page-title', 'Financial Summary')

@section('content')
@php
    $payer = $ledger['payer'] ?? null;
    $financial = $ledger['financial'] ?? null;
    $bizCredit = $ledger['business']['max_third_party_credit_limit'] ?? null;
    $effectiveCredit = null;
    if ($payer && $bizCredit !== null) {
        $cl = $payer['credit_limit'] ?? null;
        $effectiveCredit = ($cl !== null && (float) $cl > 0) ? (float) $cl : (float) $bizCredit;
    }
@endphp
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('connected-companies.show', $connection->id) }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Provider Details
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Financial Summary</h1>
            <p class="text-slate-600 mt-1 text-sm">
                {{ $insuranceCompany->name }} with {{ $connection->connected_business_name ?? 'service provider' }} — same ledger as in the provider’s Kashtre account.
            </p>
        </div>
    </div>

    @if($ledgerError)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $ledgerError }}
        </div>
    @endif

    <!-- Vendor Information & Financial Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Vendor Information</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Vendor Name</dt>
                            <dd class="mt-1 text-slate-900">{{ $insuranceCompany->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Code</dt>
                            <dd class="mt-1 text-slate-900"><code class="bg-slate-100 px-2 py-1 rounded text-xs font-mono">{{ $insuranceCompany->code ?? '—' }}</code></dd>
                        </div>
                        @if($insuranceCompany->email)
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Email</dt>
                            <dd class="mt-1 text-slate-900">{{ $insuranceCompany->email }}</dd>
                        </div>
                        @endif
                        @if($insuranceCompany->phone)
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Phone</dt>
                            <dd class="mt-1 text-slate-900">{{ $insuranceCompany->phone }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Status</dt>
                            <dd class="mt-1">
                                @if($payer)
                                    @if(($payer['status'] ?? '') === 'active')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">✓ Active</span>
                                    @elseif(($payer['status'] ?? '') === 'suspended')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">⊘ Suspended</span>
                                    @elseif(($payer['status'] ?? '') === 'blocked')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">✕ Blocked</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-700">{{ ucfirst($payer['status'] ?? '—') }}</span>
                                    @endif
                                @elseif($insuranceCompany->is_active ?? true)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Connected At</dt>
                            <dd class="mt-1 text-slate-900">{{ $connection->created_at?->format('M d, Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Financial Summary</h3>
                    @if($ledgerError)
                        <p class="text-sm text-amber-800">{{ $ledgerError }}</p>
                    @elseif($payer && $financial)
                        <dl class="space-y-3 text-sm mb-4">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Total Balance</dt>
                                <dd class="mt-1 text-lg font-semibold text-slate-900">
                                    UGX {{ number_format($financial['total_balance'] ?? 0, 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Current Balance</dt>
                                <dd class="mt-1 text-lg font-semibold {{ ($financial['current_balance'] ?? 0) < 0 ? 'text-red-600' : (($financial['current_balance'] ?? 0) > 0 ? 'text-emerald-700' : 'text-slate-900') }}">
                                    UGX {{ number_format(abs($financial['current_balance'] ?? 0), 2) }}
                                    @if(($financial['current_balance'] ?? 0) < 0)
                                        <span class="text-xs text-red-500">(Amount Owed)</span>
                                    @elseif(($financial['current_balance'] ?? 0) > 0)
                                        <span class="text-xs text-emerald-600">(Credit Available)</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Credit Limit</dt>
                                <dd class="mt-1 text-lg font-semibold text-slate-900">
                                    UGX {{ number_format($effectiveCredit ?? 0, 2) }}
                                </dd>
                                <p class="text-xs text-slate-500 mt-1">Credit limit changes are requested by the service provider in Kashtre.</p>
                            </div>
                        </dl>
                        <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'items']) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Open financial summary
                        </a>
                    @else
                        <p class="text-sm text-slate-600">{{ ($ledger['message'] ?? null) ?: 'No third-party payer account exists yet for this insurer at this provider. Balances will appear once invoices are posted in Kashtre.' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($payer && $ledger && !$ledgerError)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <button type="button" onclick="showTabCc('items')" id="tab-cc-items" class="tab-cc-btn active w-1/2 py-4 px-4 text-center border-b-2 font-medium text-sm">
                    <span class="border-b-2 border-blue-500 pb-4 px-1 text-blue-600">Items</span>
                </button>
                <button type="button" onclick="showTabCc('transactions')" id="tab-cc-transactions" class="tab-cc-btn w-1/2 py-4 px-4 text-center border-b-2 font-medium text-sm">
                    <span class="border-b-2 border-transparent pb-4 px-1 text-slate-500 hover:text-slate-700">Transactions</span>
                </button>
            </nav>
        </div>

        <div id="content-cc-items" class="tab-cc-content p-6">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="text-lg font-medium text-slate-900">Recent activity by item</h3>
                    <p class="text-sm text-slate-500">Each debit is split across invoice line items by line totals when Kashtre sends item rows; credits stay one row each.</p>
                </div>
                <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'items']) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    View full statement (by item)
                </a>
            </div>

            @if(isset($itemStatementRows) && $itemStatementRows->count() > 0)
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
                <div class="mt-4 text-center">
                    <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'items']) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                        View full statement (by item) →
                    </a>
                </div>
            @else
                <p class="text-sm text-slate-500 text-center py-8">No activity yet.</p>
            @endif
        </div>

        <div id="content-cc-transactions" class="tab-cc-content p-6 hidden">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="text-lg font-medium text-slate-900">Recent transactions</h3>
                    <p class="text-sm text-slate-500">Showing last 10 ledger rows</p>
                </div>
                <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'transactions']) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    View full statement (by transaction)
                </a>
            </div>

            @php $rows = $ledger['recent_transactions'] ?? []; @endphp
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
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                        {{ data_get($h, 'invoice.invoice_number', 'N/A') }}
                                    </td>
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
                <div class="mt-4 text-center">
                    <a href="{{ route('connected-companies.financial-statement', ['connectionId' => $connection->id, 'view' => 'transactions']) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                        View full statement (by transaction) →
                    </a>
                </div>
            @else
                <p class="text-sm text-slate-500 text-center py-8">No transactions yet.</p>
            @endif
        </div>
    </div>
    @endif
</div>

<script>
function showTabCc(name) {
    document.querySelectorAll('.tab-cc-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-cc-btn span').forEach(span => {
        span.classList.remove('border-blue-500', 'text-blue-600');
        span.classList.add('border-transparent', 'text-slate-500');
    });
    document.getElementById('content-cc-' + name).classList.remove('hidden');
    const btn = document.getElementById('tab-cc-' + name);
    const span = btn.querySelector('span');
    span.classList.remove('border-transparent', 'text-slate-500');
    span.classList.add('border-blue-500', 'text-blue-600');
}
</script>
@endsection
