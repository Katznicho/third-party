@extends('layouts.dashboard')

@section('title', 'Client Details')
@section('page-title', 'Client Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Client Details</h1>
            <p class="text-slate-600 mt-1">View complete client information and policy details</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Client
            </a>
            <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Client
                </button>
            </form>
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                ← Back to Clients
            </a>
        </div>
    </div>

    <!-- Client Information -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Personal Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Full Name</label>
                <p class="text-base text-slate-900 font-semibold">{{ $client->full_name }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">ID/Passport Number</label>
                <p class="text-base text-slate-900">{{ $client->id_passport_no }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Gender</label>
                <p class="text-base text-slate-900">{{ $client->gender ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Date of Birth</label>
                <p class="text-base text-slate-900">{{ $client->date_of_birth ? $client->date_of_birth->format('d M Y') : 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Marital Status</label>
                <p class="text-base text-slate-900">{{ $client->marital_status ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Email</label>
                <p class="text-base text-slate-900">{{ $client->email ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Payment Phone (Mobile Money)</label>
                <p class="text-base text-slate-900">{{ $client->cell_phone ?: 'N/A' }}</p>
                @if(!empty($client->cell_phone) && ($hasPendingMobileMoneyPayments ?? false))
                    <form action="{{ route('clients.check-mobile-money-payments', $client) }}" method="POST" class="mt-2 inline">
                        @csrf
                        <button type="submit" class="px-3 py-1 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Check Mobile Money Payment Status
                        </button>
                    </form>
                    <p class="mt-1 text-xs text-slate-500">
                        Use this to manually refresh Yo Payments status if the automatic check is delayed.
                    </p>
                @endif
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Home Telephone</label>
                <p class="text-base text-slate-900">{{ $client->home_telephone ?: 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Type</label>
                <p class="text-base text-slate-900">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $client->type === 'principal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ ucfirst($client->type) }}
                    </span>
                </p>
            </div>
            
            @if($client->isDependent())
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Principal Member</label>
                <p class="text-base text-slate-900">
                    <a href="{{ route('clients.show', $client->principalMember) }}" class="text-blue-600 hover:text-blue-800">
                        {{ $client->principalMember->full_name ?? 'N/A' }}
                    </a>
                </p>
            </div>
            @endif
        </div>
    </div>

    <!-- Policy Information -->
    @if($client->isPrincipal() && $client->policies->isNotEmpty())
        @foreach($client->policies as $policy)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Policy Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Policy Number</label>
                    <p class="text-base text-slate-900 font-bold text-blue-600">{{ $policy->policy_number }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Plan Type</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                            {{ $policy->plan_type }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Status</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->status === 'active' ? 'bg-green-100 text-green-800' : ($policy->status === 'inactive' && !$policy->is_paid ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                            {{ ucfirst($policy->status) }}
                        </span>
                        @if($policy->status === 'inactive' && !$policy->is_paid)
                            <a href="{{ route('clients.pay-premium', $client) }}" class="ml-2 px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">Pay Premium</a>
                        @endif
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Inception Date</label>
                    <p class="text-base text-slate-900">{{ $policy->inception_date->format('d M Y') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Expiry Date</label>
                    <p class="text-base text-slate-900">{{ $policy->expiry_date->format('d M Y') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Payment Status</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->is_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $policy->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Premium Details -->
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Premium Details</h3>
                <div class="bg-slate-50 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Total Premium:</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->total_premium, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Insurance Training Levy (0.5%):</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->insurance_training_levy, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Stamp Duty:</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->stamp_duty, 2) }}</span>
                    </div>
                    <div class="border-t-2 border-blue-500 pt-3 flex justify-between items-center">
                        <span class="text-base font-bold text-slate-900">Total Premium Due:</span>
                        <span class="text-lg font-bold text-blue-600">UGX {{ number_format($policy->total_premium_due, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment percentages, grace period & deductible contribution -->
            <div class="border-t border-slate-200 pt-6 mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Payment percentages & periods -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-3">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Payment percentages & periods</h3>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-700">Percentage payable by insurance:</span>
                        <span class="font-semibold text-slate-900">
                            {{ number_format($client->insurance_payable_percentage ?? 100, 2) }}%
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-700">Coinsurance percentage (client pays):</span>
                        <span class="font-semibold text-slate-900">
                            {{ $policy->coinsurance_percentage !== null ? number_format($policy->coinsurance_percentage, 2) . '%' : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-700">Grace period before freezing/suspension:</span>
                        <span class="font-semibold text-slate-900">
                            @if(!is_null($client->premium_grace_days))
                                {{ $client->premium_grace_days }} day(s)
                            @else
                                Uses company payment grace per method
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-700">Active period:</span>
                        <span class="font-semibold text-slate-900">
                            @if(!is_null($client->active_period_days))
                                {{ $client->active_period_days }} day(s)
                            @else
                                Uses policy inception & expiry dates
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Deductible Contribution Settings -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-3">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Deductible Contribution Settings</h3>
                    <p class="text-xs text-slate-600">
                        Configure whether copay and coinsurance payments count towards meeting the deductible.
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-start justify-between">
                            <div class="mr-3">
                                <p class="font-medium text-slate-800">Copay contributes to deductible</p>
                                <p class="text-xs text-slate-500">Copay amounts will count towards meeting the deductible.</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->copayContributesToDeductible() ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $policy->copayContributesToDeductible() ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between">
                            <div class="mr-3">
                                <p class="font-medium text-slate-800">Coinsurance contributes to deductible</p>
                                <p class="text-xs text-slate-500">Coinsurance amounts will count towards meeting the deductible.</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->coinsuranceContributesToDeductible() ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $policy->coinsuranceContributesToDeductible() ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $policyPremiumPayments = isset($premiumPayments) ? $premiumPayments->where('policy_id', $policy->id) : collect();
                $paymentMethodLabels = \App\Models\InsuranceCompany::getPaymentMethodOptions();
            @endphp
            @if($policyPremiumPayments->isNotEmpty())
            <!-- Premium payment record(s): grace period, payment method, mark as received -->
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Payment record</h3>
                <div class="space-y-4">
                    @foreach($policyPremiumPayments as $pmt)
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">PRN (Payment Reference)</span>
                                <p class="text-sm font-mono text-slate-900">{{ $pmt->payment_reference }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">Amount</span>
                                <p class="text-sm font-semibold text-slate-900">UGX {{ number_format($pmt->amount, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">Payment method</span>
                                @php
                                    $methodKey = $pmt->payment_metadata['premium_payment_method_selected'] ?? $pmt->payment_method;
                                    $methodLabel = $paymentMethodLabels[$methodKey] ?? ucfirst(str_replace('_', ' ', $methodKey ?? 'N/A'));
                                @endphp
                                <p class="text-sm text-slate-900">{{ $methodLabel }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-xs font-medium text-slate-500 uppercase">Status</span>
                                <p class="text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $pmt->status === 'completed' ? 'bg-green-100 text-green-800' : ($pmt->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst($pmt->status) }}
                                    </span>
                                </p>
                            </div>
                            @if(is_array($pmt->payment_metadata))
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">Grace period</span>
                                <p class="text-sm text-slate-900">
                                    @if(isset($pmt->payment_metadata['grace_days']))
                                        {{ $pmt->payment_metadata['grace_days'] }} day(s)
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">Due by</span>
                                <p class="text-sm text-slate-900">
                                    @if(!empty($pmt->payment_metadata['due_at']))
                                        {{ \Carbon\Carbon::parse($pmt->payment_metadata['due_at'])->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            @if(!empty($pmt->payment_metadata['due_at']) && $pmt->status === 'pending')
                            @php
                                $dueAt = \Carbon\Carbon::parse($pmt->payment_metadata['due_at'])->startOfDay();
                                $today = \Carbon\Carbon::today();
                                $daysRemaining = $today->diffInDays($dueAt, false);
                            @endphp
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase">Days remaining</span>
                                <p class="text-sm font-semibold {{ $daysRemaining < 0 ? 'text-red-700' : ($daysRemaining === 0 ? 'text-amber-700' : 'text-slate-900') }}">
                                    @if($daysRemaining > 0)
                                        {{ $daysRemaining }} day(s) remaining
                                    @elseif($daysRemaining === 0)
                                        Due today
                                    @else
                                        Overdue by {{ abs($daysRemaining) }} day(s)
                                    @endif
                                </p>
                            </div>
                            @endif
                            @endif
                        </div>
                        @if($pmt->payment_notes)
                        <p class="text-xs text-slate-600 mb-3">{{ $pmt->payment_notes }}</p>
                        @endif
                        @if($pmt->status === 'pending' && $pmt->payment_method === 'mobile_money')
                        <form action="{{ route('clients.check-mobile-money-payments', $client) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">Check Mobile Money status</button>
                        </form>
                        @endif
                        @if($pmt->status === 'pending' && $pmt->payment_method !== 'mobile_money')
                        <div class="mt-3 pt-3 border-t border-slate-200 space-y-4">
                            <p class="text-xs text-slate-600">
                                Ask the client to pay using the <strong>PRN</strong> above. Then follow the two steps below when you get the bank slip.
                            </p>

                            {{-- Step 1: Enter TID --}}
                            @if(empty($pmt->transaction_id))
                            <div class="space-y-2">
                                <h4 class="text-sm font-semibold text-slate-900">Step 1: Enter TID (Bank transaction ID)</h4>
                                <button type="button"
                                        onclick="document.getElementById('enter-tid-{{ $pmt->id }}').classList.toggle('hidden')"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-700 text-white hover:bg-slate-800">
                                    Enter TID
                                </button>
                                <div id="enter-tid-{{ $pmt->id }}" class="hidden mt-3 p-4 bg-white rounded-lg border border-slate-200 max-w-md">
                                    <form action="{{ route('payments.store-tid', $pmt) }}" method="POST">
                                        @csrf
                                        <label for="tid-{{ $pmt->id }}" class="block text-sm font-medium text-slate-700 mb-1">
                                            TID / Bank Transaction ID <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="transaction_id"
                                            id="tid-{{ $pmt->id }}"
                                            required
                                            maxlength="255"
                                            placeholder="e.g. BANK-TXN-123456"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 text-sm mb-3"
                                        >
                                        <label for="tid-reason-{{ $pmt->id }}" class="block text-sm font-medium text-slate-700 mb-1">
                                            Notes (optional)
                                        </label>
                                        <textarea
                                            name="reason"
                                            id="tid-reason-{{ $pmt->id }}"
                                            rows="2"
                                            maxlength="500"
                                            placeholder="e.g. TID from Stanbic teller at Acacia branch"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 text-sm"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-slate-500 mb-3">
                                            This step only records the TID. Status remains <strong>Pending</strong>.
                                        </p>
                                        <div class="flex gap-2">
                                            <button type="submit" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-700 text-white hover:bg-slate-800">
                                                Save TID
                                            </button>
                                            <button type="button"
                                                    onclick="document.getElementById('enter-tid-{{ $pmt->id }}').classList.add('hidden')"
                                                    class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @else
                            <div class="space-y-1">
                                <h4 class="text-sm font-semibold text-slate-900">Step 1: TID recorded</h4>
                                <p class="text-xs text-slate-700">
                                    TID: <span class="font-mono font-semibold">{{ $pmt->transaction_id }}</span>
                                </p>
                            </div>
                            @endif

                            {{-- Step 2: Verify & mark as paid (only when TID is present) --}}
                            @if(!empty($pmt->transaction_id))
                            <div class="space-y-2 border-t border-dashed border-slate-200 pt-3">
                                <h4 class="text-sm font-semibold text-slate-900">Step 2: Verify & mark as paid</h4>
                                <button type="button"
                                        onclick="document.getElementById('verify-payment-{{ $pmt->id }}').classList.toggle('hidden')"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700">
                                    Verify & mark as paid
                                </button>
                                <div id="verify-payment-{{ $pmt->id }}" class="hidden mt-3 p-4 bg-white rounded-lg border border-slate-200 max-w-md">
                                    <form action="{{ route('payments.mark-received', $pmt) }}" method="POST">
                                        @csrf
                                        <label for="verify-reason-{{ $pmt->id }}" class="block text-sm font-medium text-slate-700 mb-2">
                                            Verification notes <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            name="reason"
                                            id="verify-reason-{{ $pmt->id }}"
                                            rows="3"
                                            required
                                            maxlength="500"
                                            placeholder="e.g. Verified in bank portal – batch 123, value date 26 Feb 2026"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-slate-500 mb-3">
                                            After verification, the payment will be marked <strong>Completed</strong> and the policy will become <strong>Active</strong>.
                                        </p>
                                        <div class="flex gap-2">
                                            <button type="submit" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700">
                                                Confirm & mark paid
                                            </button>
                                            <button type="button"
                                                    onclick="document.getElementById('verify-payment-{{ $pmt->id }}').classList.add('hidden')"
                                                    class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Policy Options -->
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Policy Options</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($policy->has_deductible)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-yellow-800 mb-1">Deductible</label>
                        <p class="text-sm text-yellow-900">
                            @if($policy->deductible_amount)
                                UGX {{ number_format($policy->deductible_amount, 2) }}
                            @else
                                Enabled
                            @endif
                        </p>
                    </div>
                    @endif
                    
                    @if($policy->copay_amount)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-blue-800 mb-1">Co-payment</label>
                        <p class="text-sm text-blue-900">
                            UGX {{ number_format($policy->copay_amount, 2) }} per visit
                            @if($policy->copay_max_limit)
                                <br><span class="text-xs">Max Limit: UGX {{ number_format($policy->copay_max_limit, 2) }}</span>
                            @endif
                        </p>
                    </div>
                    @endif
                    
                    @if($policy->coinsurance_percentage)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-indigo-800 mb-1">Coinsurance</label>
                        <p class="text-sm text-indigo-900">{{ number_format($policy->coinsurance_percentage, 2) }}%</p>
                    </div>
                    @endif
                    
                    @if($policy->telemedicine_only)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-green-800 mb-1">Telemedicine Only</label>
                        <p class="text-sm text-green-900">Enabled</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Policy Benefits -->
            @if($policy->benefits->isNotEmpty())
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Policy Benefits</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Service Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Benefit Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Used Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Remaining Amount</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($policy->benefits as $benefit)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $benefit->serviceCategory->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900 text-right">UGX {{ number_format($benefit->benefit_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 text-right">UGX {{ number_format($benefit->used_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900 text-right">UGX {{ number_format($benefit->remaining_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $benefit->is_enabled ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $benefit->is_enabled ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    @endif

    <!-- Dependents -->
    @if($client->isPrincipal() && $client->dependents->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Dependents</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">ID/Passport</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Relation</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date of Birth</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($client->dependents as $dependent)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->id_passport_no }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->relation_to_principal ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->date_of_birth ? $dependent->date_of_birth->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('clients.show', $dependent) }}" class="text-blue-600 hover:text-blue-800">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Medical History Responses -->
    @if($client->medicalQuestionResponses->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Medical Questionnaire Responses</h2>
        <div class="space-y-4">
            @foreach($client->medicalQuestionResponses as $response)
            <div class="border border-slate-200 rounded-lg p-4 {{ $response->triggers_exclusion ? 'bg-red-50 border-red-300' : '' }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $response->question->question_text }}</p>
                        <p class="text-sm text-slate-600 mt-1">
                            <strong>Response:</strong> {{ ucfirst($response->response ?? 'N/A') }}
                        </p>
                        @if($response->additional_info)
                            <p class="text-xs text-slate-500 mt-1">
                                <strong>Additional Info:</strong> {{ is_array($response->additional_info) ? json_encode($response->additional_info) : $response->additional_info }}
                            </p>
                        @endif
                    </div>
                    @if($response->triggers_exclusion)
                        <span class="ml-4 px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">Exclusion Triggered</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
