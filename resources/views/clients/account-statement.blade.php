@extends('layouts.dashboard')

@section('title', 'Account Statement')
@section('page-title', 'Account Statement')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Account Statement</h1>
            <p class="text-slate-600 mt-1">{{ $client->full_name }} - {{ $client->id_passport_no }}</p>
            @if(isset($account))
                <p class="text-sm text-slate-500 mt-1">Account Number: <span class="font-semibold">{{ $account->account_number }}</span> | Status: <span class="font-semibold">{{ ucfirst($account->status) }}</span></p>
            @endif
        </div>
        <div class="flex gap-3">
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
                ← Back to Clients
            </a>
            <a href="{{ route('clients.show', $client) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                View Client
            </a>
        </div>
    </div>

    <!-- Account Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('clients.usage.guarantees', $client) }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 block hover:bg-slate-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Authorized transactions (guaranteed amounts)</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">UGX {{ number_format($totalGuaranteed ?? $totalInvoices, 2) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </a>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Total Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">UGX {{ number_format($totalPaid, 2) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Outstanding Balance</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">UGX {{ number_format($totalBalance, 2) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Net Balance</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">UGX {{ number_format($totalCredits - $totalDebits, 2) }}</p>
                </div>
                <div class="p-3 bg-slate-100 rounded-lg">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Policy usage summary cards -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('clients.usage.deductible', $client) }}" class="bg-white rounded-xl shadow-sm border border-amber-200 p-6 block hover:bg-amber-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Deductible used</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">UGX {{ number_format($totalDeductibleUsed ?? 0, 2) }}</p>
                </div>
                <div class="p-3 bg-amber-50 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </a>
        <a href="{{ route('clients.usage.copay', $client) }}" class="bg-white rounded-xl shadow-sm border border-blue-200 p-6 block hover:bg-blue-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Co-pay collected</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1">UGX {{ number_format($totalCopayUsed ?? 0, 2) }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-7a9 9 0 100 18 9 9 0 000-18z"></path>
                    </svg>
                </div>
            </div>
        </a>
        <a href="{{ route('clients.usage.coinsurance', $client) }}" class="bg-white rounded-xl shadow-sm border border-indigo-200 p-6 block hover:bg-indigo-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Co-insurance (client share)</p>
                    <p class="text-2xl font-bold text-indigo-700 mt-1">UGX {{ number_format($totalCoinsuranceUsed ?? 0, 2) }}</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582a2 2 0 011.789 1.106l1.171 2.342A2 2 0 0010.332 14H17"></path>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200">
            <nav class="flex -mb-px" aria-label="Tabs">
                <button 
                    onclick="switchAccountTab('transactions')"
                    id="account-tab-transactions"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-blue-500 text-blue-600"
                >
                    Transaction History
                </button>
                <button 
                    onclick="switchAccountTab('invoices')"
                    id="account-tab-invoices"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Invoices
                </button>
                <button
                    onclick="window.location.href='{{ route('clients.deductible-ledger', $client) }}'"
                    id="account-tab-deductible-ledger"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Deductible ledger
                </button>
                <button
                    onclick="switchAccountTab('local-exclusions')"
                    id="account-tab-local-exclusions"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Local exclusions
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Transaction History Tab -->
            <div id="account-content-transactions" class="account-tab-content">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Transaction History</h2>
                    <p class="text-sm text-slate-600 mt-1">All financial transactions for this client</p>
                </div>

                @if($transactions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Transaction #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Credit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach($transactions as $transaction)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            {{ $transaction->transaction_date ? $transaction->transaction_date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            {{ $transaction->transaction_number ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $transaction->type === 'debit' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ ucfirst($transaction->type ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900">
                                            {{ $transaction->description ?? 'N/A' }}
                                            @if($transaction->invoice)
                                                <br><span class="text-xs text-slate-500">Invoice: {{ $transaction->invoice->invoice_number }}</span>
                                            @endif
                                            @if($transaction->payment)
                                                <br><span class="text-xs text-slate-500">Payment: {{ $transaction->payment->payment_reference }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            {{ $transaction->reference_number ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                            @if($transaction->debit_amount > 0)
                                                UGX {{ number_format($transaction->debit_amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                            @if($transaction->credit_amount > 0)
                                                UGX {{ number_format($transaction->credit_amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-900 font-medium">
                                            UGX {{ number_format($transaction->balance_after ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $transaction->transaction_status === 'cleared' ? 'bg-green-100 text-green-800' : ($transaction->transaction_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-800') }}">
                                                {{ ucfirst($transaction->transaction_status ?? 'N/A') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-slate-200 mt-4">
                        {{ $transactions->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900">No transactions</h3>
                        <p class="mt-1 text-sm text-slate-500">This client has no transaction history yet.</p>
                    </div>
                @endif
            </div>

            <!-- Invoices Tab -->
            <div id="account-content-invoices" class="account-tab-content" style="display: none;">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Invoices</h2>
                    <p class="text-sm text-slate-600 mt-1">All invoices for this client</p>
                </div>

                @if($invoices->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Paid</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach($invoices as $invoice)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="hover:underline font-medium">{{ $invoice->invoice_number }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            {{ $invoice->invoice_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-900 font-medium">
                                            UGX {{ number_format($invoice->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                            UGX {{ number_format($invoice->paid_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                            UGX {{ number_format($invoice->balance_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900">No invoices</h3>
                        <p class="mt-1 text-sm text-slate-500">This client has no invoices yet.</p>
                    </div>
                @endif
            </div>

            <!-- Local Exclusions Tab -->
            <div id="account-content-local-exclusions" class="account-tab-content" style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-amber-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Local exclusions</h2>
                                <p class="text-sm text-slate-600 mt-1">
                                    These exclusions apply only to this client here.
                                </p>
                            </div>
                        </div>

                        @if(isset($localExclusions) && $localExclusions->count() > 0)
                            <ul class="divide-y divide-slate-200">
                                @foreach($localExclusions as $exclusion)
                                    <li class="py-3">
                                        <p class="text-sm text-slate-900">
                                            {{ $exclusion->reason ?? 'Local exclusion' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Added {{ $exclusion->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-500">No local exclusions for this client yet.</p>
                        @endif
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-base font-semibold text-slate-900">Add local exclusions</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Select one or more notes that apply to this client.
                        </p>

                        <form action="{{ route('clients.local-exclusions.store', $client) }}" method="POST" class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <label for="reasons" class="block text-sm font-medium text-slate-700">Exclusions</label>
                                <select
                                    id="reasons"
                                    name="reasons[]"
                                    multiple
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm client-exclusions-select"
                                >
                                    @if(isset($clientExclusionItems) && $clientExclusionItems->count() > 0)
                                        @foreach($clientExclusionItems as $item)
                                            <option value="{{ $item['name'] ?? '' }}">
                                                {{ $item['name'] ?? '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('reasons')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                    Save exclusions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Account statement tab switching
    function switchAccountTab(tabName) {
        // Hide all tab contents
        const allContents = document.querySelectorAll('.account-tab-content');
        allContents.forEach(content => {
            content.style.display = 'none';
        });
        
        // Remove active class from all tabs
        const allTabs = document.querySelectorAll('[id^="account-tab-"]');
        allTabs.forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-slate-500');
        });
        
        // Show selected tab content
        const selectedContent = document.getElementById('account-content-' + tabName);
        if (selectedContent) {
            selectedContent.style.display = 'block';
        }
        
        // Add active class to selected tab
        const selectedTab = document.getElementById('account-tab-' + tabName);
        if (selectedTab) {
            selectedTab.classList.remove('border-transparent', 'text-slate-500');
            selectedTab.classList.add('border-blue-500', 'text-blue-600');
        }
    }
    
    // Initialize first tab on page load
    document.addEventListener('DOMContentLoaded', function() {
        switchAccountTab('transactions');

        // Enhance local exclusions select with Select2 (if Select2 is loaded)
        if (window.jQuery && jQuery().select2) {
            jQuery('.client-exclusions-select').select2({
                width: '100%',
                placeholder: 'Select exclusions',
                allowClear: true
            });
        }
    });
</script>
@endsection
