@extends('layouts.dashboard')

@section('title', 'Pay Provider')
@section('page-title', 'Pay Provider')

@section('content')
@php
    $providerName = $connection->connected_business_name ?? 'Service provider';
    $defaultAmount = old('amount', $amount_owed > 0 ? number_format($amount_owed, 2, '.', '') : '');
@endphp
<div class="space-y-6 max-w-6xl">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('connected-companies.index') }}" class="hover:text-slate-700">Connected providers</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('connected-companies.show', $connection->id) }}" class="hover:text-slate-700">{{ $providerName }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('connected-companies.financial', $connection->id) }}" class="hover:text-slate-700">Financial</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-slate-900 font-medium">Payment</li>
        </ol>
    </nav>

    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pay service provider</h1>
            <p class="text-slate-600 mt-1 text-sm">
                {{ $insuranceCompany->name }} → <span class="font-medium text-slate-800">{{ $providerName }}</span>
            </p>
        </div>
        <a href="{{ route('connected-companies.financial', $connection->id) }}"
           class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 shrink-0">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to financial summary
        </a>
    </div>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- Account summary --}}
        <aside class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wide">Account summary</h2>
                </div>
                <dl class="px-5 py-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Provider</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $providerName }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Current balance</dt>
                        <dd class="mt-1 text-xl font-bold {{ $current_balance < 0 ? 'text-red-600' : ($current_balance > 0 ? 'text-emerald-700' : 'text-slate-900') }}">
                            UGX {{ number_format(abs($current_balance), 2) }}
                            @if($current_balance < 0)
                                <span class="block text-xs font-normal text-red-500 mt-0.5">You owe this amount</span>
                            @elseif($current_balance > 0)
                                <span class="block text-xs font-normal text-emerald-600 mt-0.5">Credit on account</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Your account balance</dt>
                        <dd class="mt-1 font-medium text-slate-900">UGX {{ number_format($insurerAccountBalance ?? 0, 2) }}</dd>
                    </div>
                    @if($amount_owed > 0)
                    <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2 space-y-1">
                        <dt class="text-xs font-medium text-amber-800">Settle outstanding balance</dt>
                        <dd class="text-sm text-amber-900">
                            Provider: <span class="font-semibold">UGX {{ number_format($amount_owed, 2) }}</span>
                        </dd>
                        @if(!empty($initialChargePreview))
                        <dd class="text-sm text-amber-900">
                            Service charge: <span class="font-semibold">{{ $initialChargePreview['formatted_service_charge'] }}</span>
                        </dd>
                        <dd class="text-base font-bold text-amber-950 pt-1 border-t border-amber-200">
                            Total due: {{ $initialChargePreview['formatted_total'] }}
                        </dd>
                        @else
                        <p class="text-xs text-amber-700">Service charge is calculated when you enter the amount.</p>
                        @endif
                    </div>
                    @endif
                    @if($effective_credit > 0)
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Credit limit</dt>
                        <dd class="mt-1 font-medium text-slate-900">UGX {{ number_format($effective_credit, 2) }}</dd>
                    </div>
                    @endif
                    @if($payer)
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Payer status</dt>
                        <dd class="mt-1">
                            @if(($payer['status'] ?? '') === 'active')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">{{ ucfirst($payer['status'] ?? '—') }}</span>
                            @endif
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                <p class="font-semibold mb-1">How this works</p>
                <ul class="list-disc list-inside space-y-1 text-blue-800">
                    <li>Your payment is credited to the provider ledger in Kashtre.</li>
                    <li>A vendor service charge may apply based on your payment amount.</li>
                    <li>You will receive a reference number on the confirmation screen.</li>
                </ul>
            </div>
        </aside>

        {{-- Payment form --}}
        <div class="lg:col-span-3">
            <form method="POST"
                  action="{{ route('connected-companies.financial.pay.store', $connection->id) }}"
                  id="provider-pay-form"
                  class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                @csrf
                <input type="hidden" name="service_charge" id="service_charge" value="{{ old('service_charge', $initialChargePreview['service_charge'] ?? 0) }}">

                <div class="px-6 py-5 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Payment details</h2>
                    <p class="text-sm text-slate-500 mt-1">Enter the amount to pay and how you are settling the account.</p>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="amount" class="block text-sm font-medium text-slate-700">
                                Payment amount (UGX) <span class="text-red-500">*</span>
                            </label>
                            @if($amount_owed > 0)
                            <button type="button" id="fill-owed-btn"
                                    class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                Use full amount owed
                            </button>
                            @endif
                        </div>
                        <input type="number"
                               name="amount"
                               id="amount"
                               step="0.01"
                               min="0.01"
                               required
                               value="{{ $defaultAmount }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500 @error('amount') border-red-300 @enderror"
                               placeholder="0.00">
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-slate-700 mb-1">
                            Payment method <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" id="payment_method" required
                                class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500 @error('payment_method') border-red-300 @enderror">
                            <option value="">Select method</option>
                            @foreach($payment_methods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reference" class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
                            <input type="text" name="reference" id="reference" maxlength="100"
                                   value="{{ old('reference') }}"
                                   placeholder="Auto-generated if empty"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('reference')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                            <input type="text" name="notes" id="notes" maxlength="500"
                                   value="{{ old('notes') }}"
                                   placeholder="Optional"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4" id="charge-breakdown">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Charge breakdown</p>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-600">Payment to provider</dt>
                                <dd class="font-medium text-slate-900" id="breakdown-amount">—</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-600">
                                    Kashtre service charge
                                    <span class="block text-xs font-normal text-slate-500" id="breakdown-charge-hint">On payment amount</span>
                                </dt>
                                <dd class="font-medium text-slate-900" id="breakdown-charge">—</dd>
                            </div>
                        </dl>
                        <div class="flex justify-between items-center bg-blue-50 border-t-2 border-blue-500 px-4 py-3 -mx-0">
                            <span class="text-sm font-bold text-slate-900">Total you pay</span>
                            <span class="text-lg font-bold text-blue-700" id="breakdown-total">—</span>
                        </div>
                        <p id="breakdown-hint" class="text-xs text-slate-500 mt-3 px-1">Service charge uses the same Kashtre vendor tiers as client registration.</p>
                        <p id="breakdown-error" class="text-xs text-red-600 mt-2 hidden"></p>
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="confirm" value="1" id="confirm"
                               class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500 @error('confirm') border-red-300 @enderror"
                               @checked(old('confirm'))>
                        <span class="text-sm text-slate-700 group-hover:text-slate-900">
                            I confirm this payment is correct. The total shown above (including any service charge) will be recorded on the provider ledger.
                        </span>
                    </label>
                    @error('confirm')
                        <p class="text-xs text-red-600 -mt-3">{{ $message }}</p>
                    @enderror
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <a href="{{ route('connected-companies.financial', $connection->id) }}"
                       class="inline-flex justify-center px-4 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900">
                        Cancel
                    </a>
                    <button type="submit" id="submit-pay-btn" disabled
                            class="inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Submit payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const previewUrl = @json(route('connected-companies.financial.pay.preview', $connection->id));
    const csrf = @json(csrf_token());
    const amountOwed = @json((float) $amount_owed);

    const amountInput = document.getElementById('amount');
    const fillOwedBtn = document.getElementById('fill-owed-btn');
    const breakdownAmount = document.getElementById('breakdown-amount');
    const breakdownCharge = document.getElementById('breakdown-charge');
    const breakdownTotal = document.getElementById('breakdown-total');
    const breakdownHint = document.getElementById('breakdown-hint');
    const breakdownChargeHint = document.getElementById('breakdown-charge-hint');
    const breakdownError = document.getElementById('breakdown-error');
    const hiddenCharge = document.getElementById('service_charge');
    const submitBtn = document.getElementById('submit-pay-btn');
    const initialPreview = @json($initialChargePreview);

    let previewTimer = null;
    let lastPreviewOk = false;
    let serviceCharge = parseFloat(hiddenCharge?.value) || 0;

    function formatUgx(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return '—';
        return 'UGX ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setBreakdownLoading() {
        breakdownAmount.textContent = '…';
        breakdownCharge.textContent = '…';
        breakdownTotal.textContent = '…';
        breakdownHint.textContent = 'Calculating service charge…';
        breakdownError.classList.add('hidden');
        lastPreviewOk = false;
        updateSubmitState();
    }

    function setBreakdownIdle() {
        breakdownAmount.textContent = '—';
        breakdownCharge.textContent = '—';
        breakdownTotal.textContent = '—';
        breakdownHint.textContent = 'Enter an amount to calculate the service charge.';
        lastPreviewOk = false;
        updateSubmitState();
    }

    function setBreakdownData(data) {
        serviceCharge = parseFloat(data.service_charge) || 0;
        if (hiddenCharge) {
            hiddenCharge.value = String(serviceCharge);
        }
        breakdownAmount.textContent = data.formatted_amount || formatUgx(data.amount);
        breakdownCharge.textContent = data.formatted_service_charge || formatUgx(data.service_charge);
        breakdownTotal.textContent = data.formatted_total || formatUgx(data.total);
        if (data.tier && data.tier.type === 'percentage' && breakdownChargeHint) {
            breakdownChargeHint.textContent = data.tier.amount + '% on payment amount';
        } else if (breakdownChargeHint) {
            breakdownChargeHint.textContent = 'On payment amount';
        }
        breakdownHint.textContent = data.schedule_source
            ? 'Charge tier: ' + String(data.schedule_source).replace(/_/g, ' ') + '.'
            : 'Service charge applied per Kashtre vendor schedule.';
        lastPreviewOk = true;
        updateSubmitState();
    }

    function updateSubmitState() {
        const amount = parseFloat(amountInput.value);
        const hasAmount = Number.isFinite(amount) && amount > 0;
        const confirmed = document.getElementById('confirm').checked;
        submitBtn.disabled = !(hasAmount && lastPreviewOk && confirmed);
    }

    async function fetchPreview() {
        const amount = parseFloat(amountInput.value);
        if (!Number.isFinite(amount) || amount <= 0) {
            setBreakdownIdle();
            return;
        }

        setBreakdownLoading();

        try {
            const body = new FormData();
            body.append('_token', csrf);
            body.append('amount', String(amount));
            const res = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                breakdownError.textContent = json.message || 'Could not calculate service charge.';
                breakdownError.classList.remove('hidden');
                setBreakdownIdle();
                breakdownError.classList.remove('hidden');
                return;
            }
            breakdownError.classList.add('hidden');
            setBreakdownData(json.data || {});
        } catch (e) {
            breakdownError.textContent = 'Network error. Check your connection and try again.';
            breakdownError.classList.remove('hidden');
            setBreakdownIdle();
            breakdownError.classList.remove('hidden');
        }
    }

    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(fetchPreview, 400);
    }

    if (fillOwedBtn && amountOwed > 0) {
        fillOwedBtn.addEventListener('click', function () {
            amountInput.value = amountOwed.toFixed(2);
            schedulePreview();
        });
    }

    amountInput.addEventListener('input', schedulePreview);
    document.getElementById('confirm').addEventListener('change', updateSubmitState);

    document.getElementById('provider-pay-form').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing…';
    });

    if (initialPreview && Object.keys(initialPreview).length) {
        setBreakdownData(initialPreview);
    } else if (amountInput.value) {
        schedulePreview();
    } else {
        updateSubmitState();
    }
})();
</script>
@endsection
