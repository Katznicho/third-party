@extends('layouts.dashboard')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Invoices</h1>
            <p class="text-slate-600 mt-1">Invoices from connected businesses (Kashtre)</p>
        </div>
        <div class="text-sm text-slate-500">
            <span id="invoiceCount">{{ $invoices->count() }}</span> invoice(s)
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2">
                <label for="searchInput" class="block text-sm font-medium text-slate-700 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search Invoices
                </label>
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search by invoice number, client name, phone, or business..." 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
            </div>
            
            <!-- Payment Status Filter -->
            <div>
                <label for="statusFilter" class="block text-sm font-medium text-slate-700 mb-2">Payment Status</label>
                <select 
                    id="statusFilter" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending_payment">Pending Payment</option>
                    <option value="partial">Partial</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <!-- Date Filter -->
            <div>
                <label for="dateFilter" class="block text-sm font-medium text-slate-700 mb-2">Date Range</label>
                <select 
                    id="dateFilter" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
        </div>
        
        <!-- Clear Filters Button -->
        <div class="mt-4 flex justify-end">
            <button 
                id="clearFilters" 
                class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
            >
                Clear Filters
            </button>
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
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-medium">{{ $error }}</p>
                    @if(isset($debug) && config('app.debug'))
                        <p class="text-sm mt-2 opacity-75">
                            <strong>URL:</strong> {{ $debug['url'] ?? 'N/A' }}<br>
                            <strong>Status:</strong> {{ $debug['status'] ?? 'N/A' }}
                        </p>
                    @endif
                    <p class="text-sm mt-2">
                        Please check:
                        <ul class="list-disc list-inside mt-1 ml-4">
                            <li>That the Kashtre API endpoint exists and is accessible</li>
                            <li>That your insurance company ID is correct</li>
                            <li>Check the application logs for more details</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Payment Panel (Fixed at bottom when invoices are selected) -->
    <div id="bulkPaymentPanel" class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-blue-500 shadow-2xl z-50 transform translate-y-full transition-transform duration-300 ease-in-out" style="max-height: 80vh; overflow-y: auto;">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-bold text-slate-900">
                        <svg class="w-6 h-6 inline mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Bulk Payment Processing
                    </h3>
                    <span id="selectedCount" class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">0 invoices selected</span>
                </div>
                <button onclick="closeBulkPaymentPanel()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Selected Invoices Summary -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Selected Invoices</h4>
                    <div id="selectedInvoicesList" class="space-y-2 max-h-48 overflow-y-auto">
                        <p class="text-sm text-slate-500 text-center py-4">No invoices selected</p>
                    </div>
                </div>

                <!-- Payment Details Form -->
                <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 mb-4">Payment Information</h4>
                    <form id="bulkPaymentForm" method="POST" action="{{ route('invoices.bulk-pay') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="invoice_ids" id="bulkInvoiceIds">
                        
                        <div class="space-y-4">
                            <!-- Total Amount -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border-2 border-blue-200">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Total Amount</label>
                                <div class="text-2xl font-bold text-blue-700" id="bulkTotalAmount">UGX 0.00</div>
                                <input type="hidden" name="total_amount" id="bulkTotalAmountValue" value="0">
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select name="payment_method" id="bulkPaymentMethod" required 
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Payment Method</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>

                            <!-- Mobile Money Phone Number -->
                            <div id="bulkMobileMoneyFields" style="display: none;">
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="phone_number" id="bulkPhoneNumber" 
                                       placeholder="256XXXXXXXXX"
                                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-slate-500 mt-1">Enter phone number in format: 256XXXXXXXXX</p>
                                <p class="text-xs text-blue-600 mt-1 font-medium">
                                    ✓ Payment reference will be generated automatically
                                </p>
                            </div>

                            <!-- Payment Reference (for non-mobile money) -->
                            <div id="bulkPaymentReferenceFields" style="display: none;">
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Payment Reference <span class="text-red-500">*</span>
                                </label>
                                <div class="flex space-x-2">
                                    <input type="text" name="payment_reference" id="bulkPaymentReference" 
                                           placeholder="Enter payment reference"
                                           class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="button" onclick="generateReference()" 
                                            class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium">
                                        Generate
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Enter or generate a payment reference number</p>
                            </div>

                            <!-- Hidden field for mobile money reference -->
                            <input type="hidden" name="payment_reference" id="bulkMobileMoneyReference">

                            <!-- Proof of Payment (for bank transfer and cash) -->
                            <div id="bulkProofOfPaymentFields" style="display: none;">
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Proof of Payment <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="proof_of_payment" id="bulkProofOfPayment" 
                                       accept="image/*,.pdf,.doc,.docx"
                                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-slate-500 mt-1">Upload receipt, screenshot, or document (Image, PDF, or Word)</p>
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Payment Date</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" 
                                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" 
                                          placeholder="Optional notes about this bulk payment"
                                          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex space-x-3 pt-4">
                                <button type="button" onclick="closeBulkPaymentPanel()" 
                                        class="flex-1 px-4 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors font-medium">
                                    Cancel
                                </button>
                                <button type="submit" id="bulkPaymentSubmitBtn" 
                                        class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-colors font-medium shadow-lg">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Process Payment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200 w-12">
                                <input type="checkbox" id="selectAllInvoices" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Invoice #</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Business</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Total Amount</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Balance Due</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Payment Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody" class="bg-white divide-y divide-slate-200">
                        @foreach($invoices as $invoice)
                            @php
                                $canSelect = ($invoice['payment_status'] !== 'paid' && $invoice['balance_due'] > 0);
                            @endphp
                            <tr class="invoice-row hover:bg-blue-50 transition-colors duration-150 {{ !$canSelect ? 'opacity-60' : '' }}" 
                                data-invoice-number="{{ strtolower($invoice['invoice_number'] ?? '') }}"
                                data-client-name="{{ strtolower($invoice['client_name'] ?? '') }}"
                                data-client-phone="{{ strtolower($invoice['client_phone'] ?? '') }}"
                                data-business-name="{{ strtolower($invoice['business_name'] ?? '') }}"
                                data-payment-status="{{ $invoice['payment_status'] ?? '' }}"
                                data-invoice-date="{{ \Carbon\Carbon::parse($invoice['created_at'])->format('Y-m-d') }}"
                                data-invoice-id="{{ $invoice['id'] }}"
                                data-balance-due="{{ $invoice['balance_due'] }}"
                            >
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($canSelect)
                                    <input type="checkbox" 
                                           class="invoice-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer" 
                                           value="{{ $invoice['id'] }}"
                                           data-amount="{{ $invoice['balance_due'] }}"
                                           data-invoice-number="{{ $invoice['invoice_number'] }}">
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">{{ $invoice['invoice_number'] }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900">{{ $invoice['client_name'] }}</div>
                                @if($invoice['client_id'])
                                    <div class="text-xs text-slate-500 mt-0.5">ID: {{ $invoice['client_id'] }}</div>
                                @endif
                                @if($invoice['client_phone'])
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        {{ $invoice['client_phone'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900">{{ $invoice['business_name'] ?? 'N/A' }}</div>
                                @if($invoice['branch_name'])
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ $invoice['branch_name'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-slate-900">UGX {{ number_format($invoice['total_amount'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold {{ $invoice['balance_due'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    UGX {{ number_format($invoice['balance_due'], 2) }}
                                </div>
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700">{{ \Carbon\Carbon::parse($invoice['created_at'])->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($invoice['created_at'])->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('invoices.show', $invoice['id']) }}" 
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors duration-150 shadow-sm">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Details
                                    </a>
                                    @if($invoice['payment_status'] !== 'paid' && $invoice['balance_due'] > 0)
                                        <button 
                                            onclick="showMarkPaidModal({{ $invoice['id'] }}, '{{ $invoice['invoice_number'] }}', {{ $invoice['balance_due'] }})"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors duration-150 shadow-sm"
                                        >
                                            <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Clear
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            
            <!-- No Results Message (hidden by default) -->
            <div id="noResultsMessage" class="hidden p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No invoices match your search</h3>
                <p class="mt-1 text-sm text-slate-500">Try adjusting your search or filter criteria.</p>
            </div>
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
                        Mark as Paid
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search and Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const invoiceRows = document.querySelectorAll('.invoice-row');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const invoicesTableBody = document.getElementById('invoicesTableBody');
    const invoiceCount = document.getElementById('invoiceCount');
    
    function filterInvoices() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value;
        const dateValue = dateFilter.value;
        
        let visibleCount = 0;
        const today = new Date();
        
        invoiceRows.forEach(row => {
            const invoiceNumber = row.dataset.invoiceNumber || '';
            const clientName = row.dataset.clientName || '';
            const clientPhone = row.dataset.clientPhone || '';
            const businessName = row.dataset.businessName || '';
            const paymentStatus = row.dataset.paymentStatus || '';
            const invoiceDate = row.dataset.invoiceDate || '';
            
            // Search filter
            const matchesSearch = !searchTerm || 
                invoiceNumber.includes(searchTerm) ||
                clientName.includes(searchTerm) ||
                clientPhone.includes(searchTerm) ||
                businessName.includes(searchTerm);
            
            // Status filter
            const matchesStatus = !statusValue || paymentStatus === statusValue;
            
            // Date filter
            let matchesDate = true;
            if (dateValue && invoiceDate) {
                const rowDate = new Date(invoiceDate);
                switch(dateValue) {
                    case 'today':
                        matchesDate = rowDate.toDateString() === today.toDateString();
                        break;
                    case 'week':
                        const weekStart = new Date(today);
                        weekStart.setDate(today.getDate() - today.getDay());
                        weekStart.setHours(0, 0, 0, 0);
                        matchesDate = rowDate >= weekStart;
                        break;
                    case 'month':
                        matchesDate = rowDate.getMonth() === today.getMonth() && 
                                     rowDate.getFullYear() === today.getFullYear();
                        break;
                    case 'year':
                        matchesDate = rowDate.getFullYear() === today.getFullYear();
                        break;
                }
            }
            
            // Show/hide row
            if (matchesSearch && matchesStatus && matchesDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update count
        invoiceCount.textContent = visibleCount;
        
        // Show/hide no results message
        if (visibleCount === 0 && invoiceRows.length > 0) {
            noResultsMessage.classList.remove('hidden');
            if (invoicesTableBody) {
                invoicesTableBody.style.display = 'none';
            }
        } else {
            noResultsMessage.classList.add('hidden');
            if (invoicesTableBody) {
                invoicesTableBody.style.display = '';
            }
        }
    }
    
    // Event listeners
    searchInput.addEventListener('input', filterInvoices);
    statusFilter.addEventListener('change', filterInvoices);
    dateFilter.addEventListener('change', filterInvoices);
    
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        dateFilter.value = '';
        filterInvoices();
    });
});

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

// Bulk Payment Functionality
let selectedInvoices = new Map();

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllInvoices');
    const invoiceCheckboxes = document.querySelectorAll('.invoice-checkbox');
    const bulkPaymentMethod = document.getElementById('bulkPaymentMethod');
    
    // Select All functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            invoiceCheckboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = this.checked;
                    handleInvoiceSelection(checkbox);
                }
            });
        });
    }
    
    // Individual checkbox selection
    invoiceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            handleInvoiceSelection(this);
            updateSelectAllState();
        });
    });
    
    // Payment method change handler
    if (bulkPaymentMethod) {
        bulkPaymentMethod.addEventListener('change', function() {
            const method = this.value;
            const mobileMoneyFields = document.getElementById('bulkMobileMoneyFields');
            const paymentReferenceFields = document.getElementById('bulkPaymentReferenceFields');
            const proofOfPaymentFields = document.getElementById('bulkProofOfPaymentFields');
            const phoneNumber = document.getElementById('bulkPhoneNumber');
            const paymentReference = document.getElementById('bulkPaymentReference');
            const proofOfPayment = document.getElementById('bulkProofOfPayment');
            
            // Reset all fields
            mobileMoneyFields.style.display = 'none';
            paymentReferenceFields.style.display = 'none';
            proofOfPaymentFields.style.display = 'none';
            phoneNumber.removeAttribute('required');
            paymentReference.removeAttribute('required');
            proofOfPayment.removeAttribute('required');
            phoneNumber.value = '';
            paymentReference.value = '';
            proofOfPayment.value = '';
            
            if (method === 'mobile_money') {
                mobileMoneyFields.style.display = 'block';
                phoneNumber.setAttribute('required', 'required');
                // Hide manual reference field and show auto-generated one
                const mobileMoneyRef = document.getElementById('bulkMobileMoneyReference');
                if (mobileMoneyRef) mobileMoneyRef.removeAttribute('disabled');
                // Auto-generate reference for mobile money
                generateMobileMoneyReference();
            } else if (method === 'bank_transfer' || method === 'cash') {
                paymentReferenceFields.style.display = 'block';
                proofOfPaymentFields.style.display = 'block';
                paymentReference.setAttribute('required', 'required');
                proofOfPayment.setAttribute('required', 'required');
                // Disable mobile money reference field
                const mobileMoneyRef = document.getElementById('bulkMobileMoneyReference');
                if (mobileMoneyRef) {
                    mobileMoneyRef.setAttribute('disabled', 'disabled');
                    mobileMoneyRef.value = '';
                }
            }
        });
    }
    
    // Form submission handler
    const bulkPaymentForm = document.getElementById('bulkPaymentForm');
    if (bulkPaymentForm) {
        bulkPaymentForm.addEventListener('submit', function(e) {
            if (selectedInvoices.size === 0) {
                e.preventDefault();
                alert('Please select at least one invoice to pay.');
                return false;
            }
            
            // Set invoice IDs
            document.getElementById('bulkInvoiceIds').value = Array.from(selectedInvoices.keys()).join(',');
        });
    }
});

function handleInvoiceSelection(checkbox) {
    const invoiceId = checkbox.value;
    const invoiceNumber = checkbox.dataset.invoiceNumber;
    const amount = parseFloat(checkbox.dataset.amount || 0);
    
    if (checkbox.checked) {
        selectedInvoices.set(invoiceId, {
            invoiceNumber: invoiceNumber,
            amount: amount
        });
    } else {
        selectedInvoices.delete(invoiceId);
    }
    
    updateBulkPaymentPanel();
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('selectAllInvoices');
    const invoiceCheckboxes = document.querySelectorAll('.invoice-checkbox:not(:disabled)');
    const checkedCount = Array.from(invoiceCheckboxes).filter(cb => cb.checked).length;
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkedCount === invoiceCheckboxes.length && invoiceCheckboxes.length > 0;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < invoiceCheckboxes.length;
    }
}

function updateBulkPaymentPanel() {
    const bulkPaymentPanel = document.getElementById('bulkPaymentPanel');
    const selectedCount = document.getElementById('selectedCount');
    const selectedInvoicesList = document.getElementById('selectedInvoicesList');
    const bulkTotalAmount = document.getElementById('bulkTotalAmount');
    const bulkTotalAmountValue = document.getElementById('bulkTotalAmountValue');
    
    if (selectedInvoices.size === 0) {
        bulkPaymentPanel.classList.add('translate-y-full');
        return;
    }
    
    // Show panel
    bulkPaymentPanel.classList.remove('translate-y-full');
    
    // Update count
    selectedCount.textContent = `${selectedInvoices.size} invoice${selectedInvoices.size !== 1 ? 's' : ''} selected`;
    
    // Calculate total
    let total = 0;
    selectedInvoicesList.innerHTML = '';
    
    selectedInvoices.forEach((invoice, invoiceId) => {
        total += invoice.amount;
        const invoiceItem = document.createElement('div');
        invoiceItem.className = 'flex items-center justify-between text-sm bg-white p-2 rounded border border-slate-200';
        invoiceItem.innerHTML = `
            <span class="font-medium text-slate-700">${invoice.invoiceNumber}</span>
            <span class="font-semibold text-slate-900">UGX ${invoice.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
        `;
        selectedInvoicesList.appendChild(invoiceItem);
    });
    
    // Update total
    bulkTotalAmount.textContent = `UGX ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    bulkTotalAmountValue.value = total;
}

function closeBulkPaymentPanel() {
    const bulkPaymentPanel = document.getElementById('bulkPaymentPanel');
    bulkPaymentPanel.classList.add('translate-y-full');
    
    // Uncheck all
    document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = false);
    const selectAllCheckbox = document.getElementById('selectAllInvoices');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    
    selectedInvoices.clear();
    updateBulkPaymentPanel();
}

function generateReference() {
    const prefix = 'BULK-';
    const timestamp = Date.now().toString(36).toUpperCase();
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    const reference = prefix + timestamp + '-' + random;
    document.getElementById('bulkPaymentReference').value = reference;
}

function generateMobileMoneyReference() {
    const prefix = 'MM-BULK-';
    const timestamp = Date.now().toString(36).toUpperCase();
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    const reference = prefix + timestamp + '-' + random;
    // Store in the hidden field for mobile money
    const hiddenField = document.getElementById('bulkMobileMoneyReference');
    if (hiddenField) {
        hiddenField.value = reference;
    }
}
</script>
@endsection
