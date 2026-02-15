@extends('layouts.dashboard')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payment Details</h1>
            <p class="text-slate-600 mt-1">View complete payment information</p>
        </div>
        <a href="{{ route('payments.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Payments
        </a>
    </div>

    <!-- Status Banner -->
    @php
        $statusConfig = [
            'pending' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            'completed' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            'failed' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
            'cancelled' => ['bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'text' => 'text-slate-800', 'icon' => 'M6 18L18 6M6 6l12 12'],
        ];
        $status = $statusConfig[$payment->status ?? 'pending'] ?? $statusConfig['pending'];
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="{{ $status['bg'] }} border-b {{ $status['border'] }} px-6 py-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 {{ $status['text'] }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $status['icon'] }}"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold {{ $status['text'] }}">Payment {{ ucfirst($payment->status ?? 'Pending') }}</h3>
                    <p class="text-sm {{ $status['text'] }} opacity-75">Reference: {{ $payment->payment_reference }}</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Payment Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900 border-b border-slate-200 pb-2">Payment Information</h3>
                    
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Payment Reference</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $payment->payment_reference }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Amount</dt>
                            <dd class="mt-1 text-2xl font-bold text-green-600">UGX {{ number_format($payment->amount ?? 0, 2) }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Payment Method</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'N/A')) }}
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Payment Type</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $payment->payment_type ?? 'N/A')) }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Payment Date</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $payment->payment_date ? $payment->payment_date->format('F d, Y') : 'Not set' }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Received Date</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $payment->received_date ? $payment->received_date->format('F d, Y') : 'Not set' }}
                            </dd>
                        </div>
                        
                        @if($payment->cleared_date)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Cleared Date</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->cleared_date->format('F d, Y') }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->transaction_id)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Transaction ID</dt>
                            <dd class="mt-1 text-sm font-mono text-slate-900">{{ $payment->transaction_id }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Related Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900 border-b border-slate-200 pb-2">Related Information</h3>
                    
                    <dl class="space-y-3">
                        @if($payment->invoice)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Invoice</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->invoice->invoice_number }}</dd>
                        </div>
                        @elseif(isset($payment->payment_metadata['invoice_number']))
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Invoice Number</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->payment_metadata['invoice_number'] }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->policy)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Policy</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->policy->policy_number }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->client)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Client</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->client->full_name }}</dd>
                        </div>
                        @elseif(isset($payment->payment_metadata['client_name']))
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Client Name</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->payment_metadata['client_name'] }}</dd>
                        </div>
                        @endif
                        
                        @if(isset($payment->payment_metadata['client_phone']))
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Client Phone</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->payment_metadata['client_phone'] }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->mobile_money_number)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Mobile Money Number</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->mobile_money_number }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->bank_name)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Bank Name</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->bank_name }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->account_number)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Account Number</dt>
                            <dd class="mt-1 text-sm font-mono text-slate-900">{{ $payment->account_number }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->processedBy)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Processed By</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->processedBy->name }}</dd>
                        </div>
                        @endif
                        
                        @if($payment->processed_at)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Processed At</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $payment->processed_at->format('F d, Y g:i A') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Payment Notes -->
            @if($payment->payment_notes)
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Notes</h3>
                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $payment->payment_notes }}</p>
                </div>
            </div>
            @endif

            <!-- Failure Reason -->
            @if($payment->failure_reason)
            <div class="mt-6 pt-6 border-t border-red-200">
                <h3 class="text-lg font-semibold text-red-900 mb-3">Failure Reason</h3>
                <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                    <p class="text-sm text-red-700">{{ $payment->failure_reason }}</p>
                </div>
            </div>
            @endif

            <!-- Payment Metadata (if available) -->
            @if($payment->payment_metadata && count($payment->payment_metadata) > 0)
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Additional Information</h3>
                <div class="bg-slate-50 rounded-lg p-4">
                    <dl class="space-y-2">
                        @foreach($payment->payment_metadata as $key => $value)
                            @if(!in_array($key, ['invoice_number', 'client_name', 'client_phone', 'insurance_company_id']))
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-slate-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                <dd class="text-sm text-slate-900">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </div>
            @endif

            <!-- Proof of Payment (if available) -->
            @if(isset($payment->payment_metadata['proof_of_payment_path']))
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Proof of Payment</h3>
                <div class="bg-slate-50 rounded-lg p-4">
                    <a href="{{ asset('storage/' . $payment->payment_metadata['proof_of_payment_path']) }}" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Proof of Payment
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
