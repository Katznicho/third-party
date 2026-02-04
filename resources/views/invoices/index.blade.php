@extends('layouts.dashboard')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Invoices</h1>
            <p class="text-slate-600 mt-1">Invoices from connected businesses (Kashtre)</p>
        </div>
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

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ $error }}
        </div>
    @endif

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->count() > 0)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Balance Due</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Payment Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($invoices as $invoice)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900">{{ $invoice['invoice_number'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900">{{ $invoice['client_name'] }}</div>
                                @if($invoice['client_id'])
                                    <div class="text-xs text-slate-500">ID: {{ $invoice['client_id'] }}</div>
                                @endif
                                @if($invoice['client_phone'])
                                    <div class="text-xs text-slate-500">{{ $invoice['client_phone'] }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900">{{ $invoice['business_name'] ?? 'N/A' }}</div>
                                @if($invoice['branch_name'])
                                    <div class="text-xs text-slate-500">{{ $invoice['branch_name'] }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                UGX {{ number_format($invoice['total_amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $invoice['balance_due'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                UGX {{ number_format($invoice['balance_due'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'paid' => 'bg-green-100 text-green-800',
                                        'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                        'partial' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-slate-100 text-slate-800',
                                    ];
                                    $paymentStatus = $invoice['payment_status'] ?? 'pending_payment';
                                    $statusColor = $statusColors[$paymentStatus] ?? 'bg-slate-100 text-slate-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ \Carbon\Carbon::parse($invoice['created_at'])->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('invoices.show', $invoice['id']) }}" class="text-blue-600 hover:text-blue-900 mr-3">Details</a>
                                @if($invoice['payment_status'] !== 'paid' && $invoice['balance_due'] > 0)
                                    <button 
                                        onclick="showMarkPaidModal({{ $invoice['id'] }}, '{{ $invoice['invoice_number'] }}', {{ $invoice['balance_due'] }})"
                                        class="text-green-600 hover:text-green-900"
                                    >
                                        Clear
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No invoices found</h3>
                <p class="mt-1 text-sm text-slate-500">Invoices will appear here when clients make purchases with your insurance.</p>
            </div>
        @endif
    </div>
</div>

<!-- Mark as Paid Modal -->
<div id="markPaidModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Mark Invoice as Paid</h3>
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
                        Mark as Paid
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
