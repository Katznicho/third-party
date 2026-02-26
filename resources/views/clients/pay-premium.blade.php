@extends('layouts.dashboard')

@section('title', 'Pay Premium')
@section('page-title', 'Pay Premium')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pay Premium</h1>
            <p class="text-slate-600 mt-1">Complete payment to activate policy for {{ $client->full_name }}</p>
        </div>
        <a href="{{ route('clients.show', $client) }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Client
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center space-x-3 mb-4">
            <div class="p-3 bg-emerald-100 rounded-lg">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-slate-900">Premium Payment</h3>
                <p class="text-sm text-slate-600">Policy {{ $policy->policy_number }} — pay to activate coverage</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-slate-50 p-4 rounded-lg">
                <p class="text-xs text-slate-500 mb-1">Total Premium Due</p>
                <p class="text-lg font-bold text-slate-900">UGX {{ number_format($amount, 2) }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg">
                <p class="text-xs text-slate-500 mb-1">Policy</p>
                <p class="text-base font-semibold text-slate-900">{{ $policy->policy_number }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg">
                <p class="text-xs text-slate-500 mb-1">Client</p>
                <p class="text-base font-semibold text-slate-900">{{ $client->full_name }}</p>
            </div>
        </div>

        <form action="{{ route('clients.pay-premium.process', $client) }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="payment_method" class="block text-sm font-medium text-slate-700 mb-2">
                    Payment Method <span class="text-red-500">*</span>
                </label>
                <select
                    name="payment_method"
                    id="payment_method"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                    <option value="">Select payment method</option>
                    <option value="mobile_money" {{ old('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money (Yo)</option>
                    <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">
                    <strong>Mobile Money:</strong> A payment request will be sent to the client's phone. Policy activates once payment is confirmed (cron). 
                    <strong>Cash/Bank:</strong> Policy activates immediately.
                </p>
                @error('payment_method')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="payment-phone-section" style="display: none;">
                <label for="payment_phone" class="block text-sm font-medium text-slate-700 mb-2">
                    Mobile Money Phone Number <span class="text-red-500">*</span>
                </label>
                <input
                    type="tel"
                    name="payment_phone"
                    id="payment_phone"
                    value="{{ old('payment_phone', $client->cell_phone ?? '') }}"
                    placeholder="e.g. 256701234567 or 0701234567"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-slate-500 mt-1">Format: 256XXXXXXXXX or 0XXXXXXXXX</p>
                @error('payment_phone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">Notes (optional)</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="Optional notes..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end space-x-4 pt-4 border-t border-slate-200">
                <a href="{{ route('clients.show', $client) }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Process Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var paymentMethod = document.getElementById('payment_method');
    var phoneSection = document.getElementById('payment-phone-section');
    var phoneInput = document.getElementById('payment_phone');

    function togglePhone() {
        if (paymentMethod.value === 'mobile_money') {
            phoneSection.style.display = 'block';
            phoneInput.setAttribute('required', 'required');
        } else {
            phoneSection.style.display = 'none';
            phoneInput.removeAttribute('required');
        }
    }
    paymentMethod.addEventListener('change', togglePhone);
    togglePhone();
});
</script>
@endsection
