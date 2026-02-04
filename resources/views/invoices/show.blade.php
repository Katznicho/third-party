@extends('layouts.dashboard')

@section('title', 'Invoice Details')
@section('page-title', 'Invoice Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Invoice #{{ $invoiceData['invoice']['invoice_number'] }}</h1>
            <p class="text-slate-600 mt-1">Detailed invoice statement</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-150">
            Back to Invoices
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @php
        $invoice = $invoiceData['invoice'];
        $balanceHistory = $invoiceData['balance_history'] ?? [];
    @endphp

    <!-- Invoice Information -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-medium text-slate-900 mb-4">Invoice Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Invoice Number</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['invoice_number'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Date</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ \Carbon\Carbon::parse($invoice['created_at'])->format('M d, Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $invoice['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ ucfirst($invoice['status']) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Payment Status</dt>
                        <dd class="mt-1">
                            @php
                                $paymentStatusColors = [
                                    'paid' => 'bg-green-100 text-green-800',
                                    'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                    'partial' => 'bg-blue-100 text-blue-800',
                                ];
                                $paymentStatus = $invoice['payment_status'] ?? 'pending_payment';
                                $paymentStatusColor = $paymentStatusColors[$paymentStatus] ?? 'bg-slate-100 text-slate-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $paymentStatusColor }}">
                                {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="text-lg font-medium text-slate-900 mb-4">Client Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Client Name</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['client_name'] }}</dd>
                    </div>
                    @if($invoice['client'])
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Client ID</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['client']['client_id'] ?? 'N/A' }}</dd>
                    </div>
                    @endif
                    @if($invoice['client_phone'])
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['client_phone'] }}</dd>
                    </div>
                    @endif
                    @if($invoice['business'])
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Business</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['business']['name'] }}</dd>
                    </div>
                    @endif
                    @if($invoice['branch'])
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Branch</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $invoice['branch']['name'] }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-medium text-slate-500 mb-2">Subtotal</h3>
            <p class="text-2xl font-bold text-slate-900">UGX {{ number_format($invoice['subtotal'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-medium text-slate-500 mb-2">Service Charge</h3>
            <p class="text-2xl font-bold text-slate-900">UGX {{ number_format($invoice['service_charge'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-medium text-slate-500 mb-2">Total Amount</h3>
            <p class="text-2xl font-bold text-slate-900">UGX {{ number_format($invoice['total_amount'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-medium text-slate-500 mb-2">Balance Due</h3>
            <p class="text-2xl font-bold {{ $invoice['balance_due'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                UGX {{ number_format($invoice['balance_due'], 2) }}
            </p>
            @if($invoice['payment_status'] !== 'paid' && $invoice['balance_due'] > 0)
                <button 
                    onclick="showMarkPaidModal({{ $invoiceId }}, '{{ $invoice['invoice_number'] }}', {{ $invoice['balance_due'] }})"
                    class="mt-3 w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150 text-sm font-medium"
                >
                    Clear Payment
                </button>
            @endif
        </div>
    </div>

    <!-- Invoice Items -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900 mb-4">Invoice Items</h3>
            @if(!empty($invoice['items']))
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($invoice['items'] as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $item['name'] ?? $item['item_name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $item['quantity'] ?? 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    UGX {{ number_format($item['price'] ?? $item['unit_price'] ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 text-right">
                                    UGX {{ number_format($item['total_amount'] ?? ($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-slate-500">No items found.</p>
            @endif
        </div>
    </div>

    <!-- Balance History / Transaction Statement -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-slate-900">Transaction Statement</h3>
                @if($invoice['payment_status'] !== 'paid' && $invoice['balance_due'] > 0)
                    <button 
                        onclick="showMarkPaidModal({{ $invoiceId }}, '{{ $invoice['invoice_number'] }}', {{ $invoice['balance_due'] }})"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150"
                    >
                        Clear Payment
                    </button>
                @endif
            </div>
            
            @if(!empty($balanceHistory))
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($balanceHistory as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($entry['created_at'])->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-900">
                                    {{ $entry['description'] }}
                                    @if($entry['reference_number'])
                                        <div class="text-xs text-slate-500">Ref: {{ $entry['reference_number'] }}</div>
                                    @endif
                                    @if($entry['notes'])
                                        <div class="text-xs text-slate-500">{{ $entry['notes'] }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $entry['transaction_type'] === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($entry['transaction_type']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right {{ $entry['transaction_type'] === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $entry['transaction_type'] === 'credit' ? '+' : '-' }}UGX {{ number_format($entry['amount'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 text-right">
                                    UGX {{ number_format($entry['balance'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'paid' => 'bg-green-100 text-green-800',
                                            'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                        ];
                                        $entryStatus = $entry['payment_status'] ?? 'pending_payment';
                                        $entryStatusColor = $statusColors[$entryStatus] ?? 'bg-slate-100 text-slate-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full {{ $entryStatusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $entryStatus)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-slate-500">No transaction history found.</p>
            @endif
        </div>
    </div>
</div>

<!-- Clear Payment Modal -->
<div id="markPaidModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Clear Payment</h3>
            <form id="markPaidForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
                    <input type="text" id="modalInvoiceNumber" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input type="text" id="modalAmount" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Reference</label>
                    <input type="text" name="payment_reference" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter payment reference">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Optional notes"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeMarkPaidModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Clear Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showMarkPaidModal(invoiceId, invoiceNumber, amount) {
    document.getElementById('markPaidForm').action = `/invoices/${invoiceId}/mark-paid`;
    document.getElementById('modalInvoiceNumber').value = invoiceNumber;
    document.getElementById('modalAmount').value = 'UGX ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('markPaidModal').classList.remove('hidden');
}

function closeMarkPaidModal() {
    document.getElementById('markPaidModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('markPaidModal');
    if (event.target == modal) {
        closeMarkPaidModal();
    }
}
</script>
@endsection
