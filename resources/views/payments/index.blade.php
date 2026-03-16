@extends('layouts.dashboard')

@section('title', 'Payments')
@section('page-title', 'Payments')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payments</h1>
            <p class="text-slate-600 mt-1">Manage payments and receipts</p>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Payment Reference</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Invoice Number</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Policy</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Payment Date</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">{{ $payment->payment_reference }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">
                                        @if($payment->invoice)
                                            {{ $payment->invoice->invoice_number }}
                                        @elseif($payment->payment_metadata && isset($payment->payment_metadata['invoice_number']))
                                            {{ $payment->payment_metadata['invoice_number'] }}
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">
                                        @if($payment->policy && $payment->policy->policy_number)
                                            {{ $payment->policy->policy_number }}
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-700">
                                        @if($payment->client && $payment->client->full_name)
                                            {{ $payment->client->full_name }}
                                        @elseif(isset($payment->payment_metadata['client_name']) && $payment->payment_metadata['client_name'])
                                            {{ $payment->payment_metadata['client_name'] }}
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-semibold text-green-600">
                                        UGX {{ number_format($payment->amount ?? 0, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'N/A')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                            'completed' => 'bg-green-100 text-green-800 border-green-200',
                                            'failed' => 'bg-red-100 text-red-800 border-red-200',
                                            'cancelled' => 'bg-slate-100 text-slate-800 border-slate-200',
                                        ];
                                        $statusColor = $statusColors[$payment->status ?? 'pending'] ?? 'bg-slate-100 text-slate-800 border-slate-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusColor }}">
                                        {{ ucfirst($payment->status ?? 'Pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-600">
                                        @if($payment->payment_date)
                                            {{ $payment->payment_date->format('M d, Y') }}
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('payments.show', $payment) }}" 
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-150 shadow-sm">
                                            <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $payments->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No payments</h3>
                <p class="mt-1 text-sm text-slate-500">Payments will appear here when invoices are marked as paid.</p>
            </div>
        @endif
    </div>
</div>
@endsection
