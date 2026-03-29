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
        $split = $invoice['insurance_split'] ?? null;
        $authClientTotal = (float) ($split['client_total'] ?? $invoice['insurance_client_total'] ?? 0);
        $authInsuranceTotal = (float) ($split['insurance_total'] ?? $invoice['insurance_insurance_total'] ?? 0);
        $hasAuthSplit = $authClientTotal > 0 || $authInsuranceTotal > 0;
        $payments = $invoicePayments ?? collect();
        $clientPaidRecorded = $payments->filter(function ($p) {
            return $p->status === 'completed' && data_get($p->payment_metadata, 'client_portion') === true;
        })->sum('amount');
        $insurerPaidRecorded = $payments->filter(function ($p) {
            return $p->status === 'completed' && data_get($p->payment_metadata, 'client_portion') !== true;
        })->sum('amount');
        $clientRemaining = max(0, $authClientTotal - (float) $clientPaidRecorded);
        $insurerRemaining = max(0, $authInsuranceTotal - (float) $insurerPaidRecorded);
        $suggestedInsurerPayment = $hasAuthSplit ? $insurerRemaining : (float) ($invoice['balance_due'] ?? 0);
        $showInsurerClearButton = ($invoice['payment_status'] ?? '') !== 'paid'
            && (float) ($invoice['balance_due'] ?? 0) > 0
            && (!$hasAuthSplit || $insurerRemaining > 0.009);
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
            <p class="text-xs text-slate-500 mt-1">On Kashtre (full invoice)</p>
            @if($showInsurerClearButton)
                <button 
                    type="button"
                    onclick="showMarkPaidModal({{ $invoiceId }}, '{{ $invoice['invoice_number'] }}', {{ json_encode($suggestedInsurerPayment) }})"
                    class="mt-3 w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150 text-sm font-medium"
                >
                    Record insurer payment
                </button>
                @if($hasAuthSplit)
                    <p class="text-xs text-slate-500 mt-2">Suggested amount: insurer share still due (UGX {{ number_format($suggestedInsurerPayment, 2) }}).</p>
                @endif
            @endif
        </div>
    </div>

    @if($hasAuthSplit)
    <!-- Authorized split + recorded payments (this app) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-sm border border-indigo-100 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Authorized amounts</h3>
            <p class="text-sm text-slate-600 mb-4">From insurance authorization (client vs insurer share of this invoice).</p>
            <dl class="space-y-3">
                <div class="flex justify-between items-center border-b border-indigo-100 pb-2">
                    <dt class="text-sm font-medium text-slate-600">Client responsibility</dt>
                    <dd class="text-sm font-bold text-slate-900">UGX {{ number_format($authClientTotal, 2) }}</dd>
                </div>
                <div class="flex justify-between items-center border-b border-indigo-100 pb-2">
                    <dt class="text-sm font-medium text-slate-600">Insurer responsibility</dt>
                    <dd class="text-sm font-bold text-slate-900">UGX {{ number_format($authInsuranceTotal, 2) }}</dd>
                </div>
                @if(!empty($invoice['insurance_authorization_reference']) || !empty($split['authorization_reference']))
                    <div class="text-xs text-slate-500">
                        Ref: {{ $invoice['insurance_authorization_reference'] ?? $split['authorization_reference'] ?? '—' }}
                    </div>
                @endif
            </dl>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl shadow-sm border border-emerald-100 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Recorded in this app</h3>
            <p class="text-sm text-slate-600 mb-4">Paid amounts booked here (Kashtre client portion + your insurer payments).</p>
            <dl class="space-y-3">
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-slate-600">Paid by client (recorded)</dt>
                    <dd class="text-sm font-semibold text-emerald-800">UGX {{ number_format((float) $clientPaidRecorded, 2) }}</dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-slate-600">Paid by insurer (recorded)</dt>
                    <dd class="text-sm font-semibold text-emerald-800">UGX {{ number_format((float) $insurerPaidRecorded, 2) }}</dd>
                </div>
                <div class="border-t border-emerald-100 pt-3 mt-2 space-y-2">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm font-medium text-slate-700">Remaining — client</dt>
                        <dd class="text-sm font-bold {{ $clientRemaining > 0 ? 'text-amber-700' : 'text-emerald-700' }}">UGX {{ number_format($clientRemaining, 2) }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm font-medium text-slate-700">Remaining — insurer</dt>
                        <dd class="text-sm font-bold {{ $insurerRemaining > 0 ? 'text-amber-700' : 'text-emerald-700' }}">UGX {{ number_format($insurerRemaining, 2) }}</dd>
                    </div>
                </div>
            </dl>
        </div>
    </div>
    @else
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-600">
        No insurance authorization split is stored for this invoice. Payment rows below still list any payments linked to this Kashtre invoice ID.
    </div>
    @endif

    <!-- Payments linked to this invoice (third-party app) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900 mb-2">Payments (this insurer app)</h3>
            <p class="text-sm text-slate-600 mb-4">Includes client-portion payments received via Kashtre and insurer payments you record when clearing the bill.</p>
            @if($payments->isEmpty())
                <p class="text-slate-500 text-sm">No payments recorded yet for this invoice.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Payer</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($payments as $p)
                                @php
                                    $isClientPortion = data_get($p->payment_metadata, 'client_portion') === true;
                                    $payerLabel = $isClientPortion ? 'Client' : 'Insurer';
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900">{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('M d, Y') : '—' }}</td>
                                    <td class="px-4 py-3 text-sm font-mono text-slate-800">{{ $p->payment_reference }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isClientPortion ? 'bg-violet-100 text-violet-800' : 'bg-sky-100 text-sky-800' }}">
                                            {{ $payerLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-right text-slate-900">UGX {{ number_format((float) $p->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $p->payment_method ?? '—')) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $p->status === 'completed' ? 'bg-green-100 text-green-800' : ($p->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                            {{ ucfirst($p->status ?? '—') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                @if($showInsurerClearButton)
                    <button 
                        type="button"
                        onclick="showMarkPaidModal({{ $invoiceId }}, '{{ $invoice['invoice_number'] }}', {{ json_encode($suggestedInsurerPayment) }})"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150"
                    >
                        Record insurer payment
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">Record insurer payment</h3>
            <form id="markPaidForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
                    <input type="text" id="modalInvoiceNumber" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input type="text" id="modalAmount" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    <input type="hidden" id="modalAmountValue" name="amount">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                    <select name="payment_method" id="paymentMethod" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Payment Method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="mb-4" id="mobileMoneyFields" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone_number" id="phoneNumber" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="256XXXXXXXXX">
                    <p class="text-xs text-gray-500 mt-1">Enter phone number in format: 256XXXXXXXXX</p>
                    <p class="text-xs text-blue-600 mt-1">✓ Payment will be processed automatically via mobile money</p>
                </div>
                <div class="mb-4" id="proofOfPaymentFields" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proof of Payment <span class="text-red-500">*</span></label>
                    <input type="file" name="proof_of_payment" id="proofOfPayment" accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload receipt, screenshot, or document (Image, PDF, or Word)</p>
                    <p class="text-xs text-yellow-600 mt-1">⚠ Payment will be reviewed before being marked as paid</p>
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
                    <button type="submit" id="submitPaymentBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Submit
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
    document.getElementById('modalAmountValue').value = amount;
    document.getElementById('markPaidModal').classList.remove('hidden');
    
    // Reset form
    document.getElementById('paymentMethod').value = '';
    document.getElementById('phoneNumber').value = '';
    document.getElementById('proofOfPayment').value = '';
    document.getElementById('mobileMoneyFields').style.display = 'none';
    document.getElementById('proofOfPaymentFields').style.display = 'none';
}

// Show/hide fields based on payment method
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('paymentMethod');
    const mobileMoneyFields = document.getElementById('mobileMoneyFields');
    const proofOfPaymentFields = document.getElementById('proofOfPaymentFields');
    const phoneNumber = document.getElementById('phoneNumber');
    const proofOfPayment = document.getElementById('proofOfPayment');
    
    if (paymentMethod) {
        paymentMethod.addEventListener('change', function() {
            const method = this.value;
            
            // Reset all fields
            mobileMoneyFields.style.display = 'none';
            proofOfPaymentFields.style.display = 'none';
            phoneNumber.removeAttribute('required');
            proofOfPayment.removeAttribute('required');
            phoneNumber.value = '';
            proofOfPayment.value = '';
            
            if (method === 'mobile_money') {
                mobileMoneyFields.style.display = 'block';
                phoneNumber.setAttribute('required', 'required');
            } else if (method === 'bank_transfer' || method === 'cash') {
                proofOfPaymentFields.style.display = 'block';
                proofOfPayment.setAttribute('required', 'required');
            }
        });
    }
});

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
