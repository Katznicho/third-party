@extends('layouts.dashboard')

@section('title', 'Pay Provider')
@section('page-title', 'Pay Provider')

@section('content')
@php
    $providerName = $connection->connected_business_name ?? 'Service provider';
    $hasOutstanding = ! empty($outstanding_entries);
    $oldSelectedIds = array_map('intval', (array) old('history_ids', []));
    $defaultAmount = old('amount', '');
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
                    <li>Select the line items you want to clear, then complete payment.</li>
                    <li>Cash or bank: recorded on the provider ledger immediately.</li>
                    <li>Mobile money: collected from the phone first; the ledger updates after Yo confirms payment.</li>
                    <li>A Kashtre vendor service charge applies to the payment amount (shown in the breakdown).</li>
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

                <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Step 1</p>
                    <h2 class="text-lg font-semibold text-slate-900 mt-1">Choose items to clear</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        @if($hasOutstanding)
                            Tick every line you want this payment to settle. You must select at least one item before you can continue.
                        @else
                            There are no outstanding line items. You can still post a payment to the provider account.
                        @endif
                    </p>
                </div>

                @if($hasOutstanding)
                <div class="px-6 py-5 border-b border-amber-200 bg-amber-50/40" id="clear-items-section">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Outstanding items</p>
                            <p class="text-xs text-slate-600 mt-0.5" id="selection-hint">Nothing selected yet</p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <button type="button" id="select-all-items-btn"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                                Select all
                            </button>
                            <button type="button" id="clear-selection-btn"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                                Clear selection
                            </button>
                        </div>
                    </div>

                    <ul class="space-y-2" id="outstanding-item-list">
                        @foreach($outstanding_entries as $index => $entry)
                        @php $entryId = (int) ($entry['id'] ?? 0); @endphp
                        <li>
                            <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors
                                          {{ in_array($entryId, $oldSelectedIds, true) ? 'border-blue-500 bg-blue-50/80' : 'border-slate-200 bg-white hover:border-slate-300' }}
                                          item-select-row"
                                   data-entry-id="{{ $entryId }}"
                                   data-entry-index="{{ $index }}"
                                   data-entry-amount="{{ (float) ($entry['amount'] ?? 0) }}">
                                <input type="checkbox"
                                       name="history_ids[]"
                                       value="{{ $entryId }}"
                                       class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500 item-checkbox"
                                       @checked(in_array($entryId, $oldSelectedIds, true))>
                                <span class="flex-1 min-w-0">
                                    <span class="flex justify-between gap-3">
                                        <span class="font-medium text-slate-900">{{ $entry['description'] ?? 'Transaction' }}</span>
                                        <span class="shrink-0 font-bold text-slate-900">UGX {{ number_format((float) ($entry['amount'] ?? 0), 2) }}</span>
                                    </span>
                                    <span class="block text-xs text-slate-500 mt-1">
                                        {{ $entry['date'] ?? '' }}
                                        @if(!empty($entry['invoice_number']))
                                            · Invoice {{ $entry['invoice_number'] }}
                                        @endif
                                        @if(!empty($entry['client_name']))
                                            · {{ $entry['client_name'] }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        </li>
                        @endforeach
                    </ul>

                    <p id="selection-error" class="text-sm text-red-600 mt-3 hidden"></p>
                    @error('history_ids')
                        <p class="text-sm text-red-600 mt-3">{{ $message }}</p>
                    @enderror

                    <div class="mt-5 flex justify-between items-center rounded-xl bg-slate-900 text-white px-5 py-4 shadow-sm">
                        <span class="text-sm font-medium text-slate-300">Selected subtotal</span>
                        <span class="text-xl font-bold tabular-nums tracking-tight" id="selected-subtotal">UGX 0.00</span>
                    </div>
                </div>
                @endif

                <div id="payment-step-2" class="{{ $hasOutstanding ? 'opacity-50 pointer-events-none' : '' }} transition-opacity duration-200">
                <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Step 2</p>
                    <h2 class="text-lg font-semibold text-slate-900 mt-1">Payment details</h2>
                    <p class="text-sm text-slate-600 mt-1" id="payment-step-hint">
                        @if($hasOutstanding)
                            Select items above to unlock payment.
                        @else
                            Enter the amount and how you are paying.
                        @endif
                    </p>
                </div>

                <div class="px-6 py-6 space-y-8">
                    {{-- Amount --}}
                    <section>
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div>
                                <label for="amount" class="text-sm font-semibold text-slate-900">
                                    Payment amount <span class="text-red-500">*</span>
                                </label>
                                <p class="text-xs text-slate-500 mt-0.5">Amount sent to the provider before service charge</p>
                            </div>
                            @if($hasOutstanding)
                            <button type="button" id="pay-selected-total-btn"
                                    class="shrink-0 text-xs font-medium text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg px-3 py-1.5 transition-colors hidden">
                                Reset to selected total
                            </button>
                            @elseif($amount_owed > 0)
                            <button type="button" id="fill-owed-btn"
                                    class="shrink-0 text-xs font-medium text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg px-3 py-1.5 transition-colors">
                                Use full balance
                            </button>
                            @endif
                        </div>
                        <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-slate-300 focus-within:ring-2 focus-within:ring-blue-600 @error('amount') ring-red-300 focus-within:ring-red-500 @enderror">
                            <span class="inline-flex items-center rounded-l-lg border-r border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-600 select-none">UGX</span>
                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   step="0.01"
                                   min="0.01"
                                   required
                                   value="{{ $defaultAmount }}"
                                   placeholder="0.00"
                                   class="pay-amount-input block w-full min-w-0 flex-1 border-0 bg-white py-3 pl-4 pr-4 text-base font-semibold text-slate-900 tabular-nums placeholder:font-normal placeholder:text-slate-400 focus:ring-0">
                        </div>
                        @if($hasOutstanding)
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Prefilled from your selection. You may reduce this for a partial payment on the last selected line.</p>
                        @endif
                        @error('amount')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- Payment method --}}
                    <section>
                        <label for="payment_method" class="text-sm font-semibold text-slate-900">
                            Payment method <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5 mb-3">How you will settle this payment</p>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" required
                                    class="w-full appearance-none rounded-lg border-0 py-3 pl-4 pr-10 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-blue-600 @error('payment_method') ring-red-300 @enderror">
                                <option value="">Choose a payment method</option>
                                @foreach($payment_methods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </div>
                        @error('payment_method')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5">
                                <p class="text-xs font-semibold text-slate-800 mb-1">Mobile money</p>
                                <p class="text-xs text-slate-600 leading-relaxed">Collection is sent to the phone number you provide. The provider ledger updates after payment is confirmed.</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5">
                                <p class="text-xs font-semibold text-slate-800 mb-1">Cash or bank</p>
                                <p class="text-xs text-slate-600 leading-relaxed">Recorded on the provider ledger immediately once you submit.</p>
                            </div>
                        </div>
                    </section>

                    <section id="payment-phone-section" class="hidden">
                        <label for="payment_phone" class="text-sm font-semibold text-slate-900">
                            Mobile money number <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5 mb-3">Including country code, e.g. 256701234567</p>
                        <input type="tel"
                               name="payment_phone"
                               id="payment_phone"
                               value="{{ old('payment_phone') }}"
                               placeholder="256701234567"
                               class="w-full rounded-lg border-0 py-3 px-4 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600">
                        <p class="text-xs text-slate-500 mt-2">The total due (including service charge) will be requested from this number.</p>
                        @error('payment_phone')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- Optional fields --}}
                    <section class="border-t border-slate-100 pt-6">
                        <p class="text-sm font-semibold text-slate-900 mb-4">Additional details</p>

                        <div id="reference-auto-notice" class="hidden mb-5 rounded-lg border border-blue-200 bg-blue-50/80 px-4 py-3.5">
                            <div class="flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-900">Payment reference</p>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        A unique reference is generated automatically when you submit. It is used for mobile money collection and on the provider ledger after confirmation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" id="reference-notes-grid">
                            <div id="reference-manual-field">
                                <label for="reference" class="block text-xs font-medium text-slate-600 mb-1.5">Payment reference</label>
                                <input type="text" name="reference" id="reference" maxlength="100"
                                       value="{{ old('reference') }}"
                                       placeholder="Leave blank to auto-generate"
                                       class="w-full rounded-lg border-0 py-2.5 px-3 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600">
                                <p class="text-xs text-slate-500 mt-1.5">Optional. Used on the provider ledger for cash or bank payments.</p>
                                @error('reference')
                                    <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                                @enderror
                            </div>
                            <div id="notes-field" class="sm:col-span-1">
                                <label for="notes" class="block text-xs font-medium text-slate-600 mb-1.5">Internal notes</label>
                                <input type="text" name="notes" id="notes" maxlength="500"
                                       value="{{ old('notes') }}"
                                       placeholder="Optional"
                                       class="w-full rounded-lg border-0 py-2.5 px-3 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                    </section>

                    {{-- Summary --}}
                    <section id="charge-breakdown" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden ring-1 ring-slate-900/5">
                        <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                            <h3 class="text-sm font-semibold text-slate-900">Payment summary</h3>
                            <p class="text-xs text-slate-500 mt-0.5" id="breakdown-hint">Enter an amount to see totals</p>
                        </div>
                        <div class="px-5 py-4 space-y-3 text-sm" id="breakdown-lines">
                            <div class="flex justify-between items-center gap-4 py-1">
                                <span class="text-slate-600">Provider payment</span>
                                <span class="font-semibold text-slate-900 tabular-nums" id="breakdown-amount">—</span>
                            </div>
                            <div class="flex justify-between items-start gap-4 py-1 border-t border-dashed border-slate-100 pt-3">
                                <span class="text-slate-600">
                                    Kashtre service charge
                                    <span class="block text-xs font-normal text-slate-400 mt-0.5" id="breakdown-charge-hint">Calculated on provider payment</span>
                                </span>
                                <span class="font-semibold text-slate-900 tabular-nums shrink-0" id="breakdown-charge">—</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center gap-4 px-5 py-4 bg-slate-900 text-white">
                            <div>
                                <p class="text-xs font-medium text-slate-300 uppercase tracking-wide">Total due</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Including service charge</p>
                            </div>
                            <p class="text-2xl font-bold tabular-nums tracking-tight" id="breakdown-total">—</p>
                        </div>
                        <p id="breakdown-error" class="hidden text-xs text-red-700 bg-red-50 border-t border-red-100 px-5 py-3"></p>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="confirm" value="1" id="confirm"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 @error('confirm') border-red-300 @enderror"
                                   @checked(old('confirm'))>
                            <span class="text-sm text-slate-700 leading-relaxed">
                                I confirm the payment summary is correct and authorise this amount to be recorded on the provider ledger.
                            </span>
                        </label>
                        @error('confirm')
                            <p class="text-xs text-red-600 mt-2 ml-7">{{ $message }}</p>
                        @enderror
                    </section>
                </div>
                </div>{{-- /payment-step-2 --}}

                <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <a href="{{ route('connected-companies.financial', $connection->id) }}"
                       class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" id="submit-pay-btn" disabled
                            class="inline-flex justify-center items-center px-8 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Confirm and pay
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
    const hasOutstanding = @json($hasOutstanding);
    const outstandingEntries = @json($outstanding_entries ?? []);

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
    const paymentStep2 = document.getElementById('payment-step-2');
    const paymentStepHint = document.getElementById('payment-step-hint');
    const selectedSubtotalEl = document.getElementById('selected-subtotal');
    const selectionHint = document.getElementById('selection-hint');
    const selectionError = document.getElementById('selection-error');
    const itemCheckboxes = Array.from(document.querySelectorAll('.item-checkbox'));
    const selectAllBtn = document.getElementById('select-all-items-btn');
    const clearSelectionBtn = document.getElementById('clear-selection-btn');
    const paySelectedTotalBtn = document.getElementById('pay-selected-total-btn');

    let previewTimer = null;
    let lastPreviewOk = false;
    let serviceCharge = parseFloat(document.getElementById('service_charge')?.value) || 0;

    function formatUgx(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return '—';
        return 'UGX ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getSelectedIds() {
        return itemCheckboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return parseInt(cb.value, 10); });
    }

    function getSelectedTotal() {
        return itemCheckboxes.filter(function (cb) { return cb.checked; })
            .reduce(function (sum, cb) {
                const row = cb.closest('.item-select-row');
                const amount = parseFloat(row?.dataset.entryAmount || '0');
                return sum + (Number.isFinite(amount) ? amount : 0);
            }, 0);
    }

    function enforceContiguousSelection(changedCheckbox) {
        const index = parseInt(changedCheckbox.closest('.item-select-row')?.dataset.entryIndex || '-1', 10);
        if (index < 0) return;

        if (changedCheckbox.checked) {
            for (let i = 0; i <= index; i++) {
                if (itemCheckboxes[i]) itemCheckboxes[i].checked = true;
            }
        } else {
            for (let i = index; i < itemCheckboxes.length; i++) {
                if (itemCheckboxes[i]) itemCheckboxes[i].checked = false;
            }
        }
    }

    function updateRowStyles() {
        document.querySelectorAll('.item-select-row').forEach(function (row) {
            const cb = row.querySelector('.item-checkbox');
            if (!cb) return;
            row.classList.toggle('border-blue-500', cb.checked);
            row.classList.toggle('bg-blue-50/80', cb.checked);
            row.classList.toggle('border-slate-200', !cb.checked);
            row.classList.toggle('bg-white', !cb.checked);
        });
    }

    function syncSelectionUi() {
        const selectedIds = getSelectedIds();
        const selectedTotal = getSelectedTotal();
        const hasSelection = selectedIds.length > 0;

        if (selectedSubtotalEl) {
            selectedSubtotalEl.textContent = formatUgx(selectedTotal);
        }
        if (selectionHint) {
            selectionHint.textContent = hasSelection
                ? selectedIds.length + ' item' + (selectedIds.length === 1 ? '' : 's') + ' selected'
                : 'Nothing selected yet';
        }
        if (selectionError) {
            selectionError.classList.add('hidden');
        }

        if (hasOutstanding && amountInput) {
            amountInput.value = hasSelection ? selectedTotal.toFixed(2) : '';
        }

        if (paymentStep2) {
            paymentStep2.classList.toggle('opacity-50', hasOutstanding && !hasSelection);
            paymentStep2.classList.toggle('pointer-events-none', hasOutstanding && !hasSelection);
        }
        if (paymentStepHint) {
            paymentStepHint.textContent = hasOutstanding && !hasSelection
                ? 'Select items above to unlock payment.'
                : 'Complete payment for the selected items.';
        }
        if (paySelectedTotalBtn) {
            paySelectedTotalBtn.classList.toggle('hidden', !hasSelection);
        }

        updateRowStyles();

        if (!hasOutstanding || hasSelection) {
            schedulePreview();
        } else {
            setBreakdownIdle();
        }
    }

    function setBreakdownLoading() {
        breakdownAmount.textContent = '…';
        breakdownCharge.textContent = '…';
        breakdownTotal.textContent = '…';
        if (breakdownHint) breakdownHint.textContent = 'Calculating totals…';
        breakdownError.classList.add('hidden');
        lastPreviewOk = false;
        updateSubmitState();
    }

    function setBreakdownIdle() {
        breakdownAmount.textContent = '—';
        breakdownCharge.textContent = '—';
        breakdownTotal.textContent = '—';
        if (breakdownHint) {
            breakdownHint.textContent = hasOutstanding
                ? 'Select items and enter an amount'
                : 'Enter an amount to see totals';
        }
        if (breakdownChargeHint) breakdownChargeHint.textContent = 'Calculated on provider payment';
        lastPreviewOk = false;
        updateSubmitState();
    }

    function setBreakdownData(data) {
        serviceCharge = parseFloat(data.service_charge) || 0;
        const hiddenCharge = document.getElementById('service_charge');
        if (hiddenCharge) hiddenCharge.value = String(serviceCharge);
        breakdownAmount.textContent = data.formatted_amount || formatUgx(data.amount);
        breakdownCharge.textContent = data.formatted_service_charge || formatUgx(data.service_charge);
        breakdownTotal.textContent = data.formatted_total || formatUgx(data.total);
        if (data.tier && data.tier.type === 'percentage' && breakdownChargeHint) {
            breakdownChargeHint.textContent = data.tier.amount + '% of provider payment';
        } else if (breakdownChargeHint) {
            breakdownChargeHint.textContent = 'Calculated on provider payment';
        }
        if (breakdownHint) {
            breakdownHint.textContent = data.schedule_source
                ? 'Vendor tier: ' + String(data.schedule_source).replace(/_/g, ' ')
                : 'Ready to submit';
        }
        lastPreviewOk = true;
        updateSubmitState();
    }

    function updateSubmitState() {
        const amount = parseFloat(amountInput?.value || '0');
        const hasAmount = Number.isFinite(amount) && amount > 0;
        const confirmed = document.getElementById('confirm')?.checked;
        const hasSelection = !hasOutstanding || getSelectedIds().length > 0;
        submitBtn.disabled = !(hasAmount && lastPreviewOk && confirmed && hasSelection);
    }

    async function fetchPreview() {
        const amount = parseFloat(amountInput?.value || '0');
        if (!Number.isFinite(amount) || amount <= 0) {
            setBreakdownIdle();
            return;
        }
        if (hasOutstanding && getSelectedIds().length === 0) {
            setBreakdownIdle();
            return;
        }

        setBreakdownLoading();

        try {
            const body = new FormData();
            body.append('_token', csrf);
            body.append('amount', String(amount));
            getSelectedIds().forEach(function (id) {
                body.append('history_ids[]', String(id));
            });
            const res = await fetch(previewUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                breakdownError.textContent = json.message || 'Could not calculate service charge.';
                breakdownError.classList.remove('hidden');
                if (selectionError && hasOutstanding) {
                    selectionError.textContent = json.message || 'Adjust your selection or amount.';
                    selectionError.classList.remove('hidden');
                }
                setBreakdownIdle();
                return;
            }
            breakdownError.classList.add('hidden');
            if (selectionError) selectionError.classList.add('hidden');
            setBreakdownData(json.data || {});
        } catch (e) {
            breakdownError.textContent = 'Network error. Check your connection and try again.';
            breakdownError.classList.remove('hidden');
            setBreakdownIdle();
        }
    }

    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(fetchPreview, 400);
    }

    itemCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            enforceContiguousSelection(cb);
            syncSelectionUi();
        });
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            itemCheckboxes.forEach(function (cb) { cb.checked = true; });
            syncSelectionUi();
        });
    }
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            itemCheckboxes.forEach(function (cb) { cb.checked = false; });
            syncSelectionUi();
        });
    }

    if (fillOwedBtn && amountOwed > 0 && !hasOutstanding) {
        fillOwedBtn.addEventListener('click', function () {
            amountInput.value = amountOwed.toFixed(2);
            schedulePreview();
        });
    }

    if (amountInput) {
        amountInput.addEventListener('input', schedulePreview);
    }

    if (paySelectedTotalBtn) {
        paySelectedTotalBtn.addEventListener('click', function () {
            const total = getSelectedTotal();
            if (total > 0 && amountInput) {
                amountInput.value = total.toFixed(2);
                schedulePreview();
            }
        });
    }

    document.getElementById('confirm')?.addEventListener('change', updateSubmitState);

    document.getElementById('provider-pay-form').addEventListener('submit', function (e) {
        if (hasOutstanding && getSelectedIds().length === 0) {
            e.preventDefault();
            if (selectionError) {
                selectionError.textContent = 'Select at least one item to clear before you continue.';
                selectionError.classList.remove('hidden');
            }
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Processing…';
    });

    syncSelectionUi();

    const paymentMethodSelect = document.getElementById('payment_method');
    const phoneSection = document.getElementById('payment-phone-section');
    const phoneInput = document.getElementById('payment_phone');

    const referenceAutoNotice = document.getElementById('reference-auto-notice');
    const referenceManualField = document.getElementById('reference-manual-field');
    const referenceInput = document.getElementById('reference');
    const notesField = document.getElementById('notes-field');
    const referenceNotesGrid = document.getElementById('reference-notes-grid');

    function togglePaymentMethodSections() {
        const isMobile = paymentMethodSelect && paymentMethodSelect.value === 'mobile_money';

        if (phoneSection) {
            phoneSection.classList.toggle('hidden', !isMobile);
        }
        if (phoneInput) {
            if (isMobile) phoneInput.setAttribute('required', 'required');
            else phoneInput.removeAttribute('required');
        }

        if (referenceAutoNotice) {
            referenceAutoNotice.classList.toggle('hidden', !isMobile);
        }
        if (referenceManualField) {
            referenceManualField.classList.toggle('hidden', isMobile);
        }
        if (referenceInput && isMobile) {
            referenceInput.value = '';
        }
        if (notesField && referenceNotesGrid) {
            notesField.classList.toggle('sm:col-span-2', isMobile);
        }
    }

    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', togglePaymentMethodSections);
        togglePaymentMethodSections();
    }
})();
</script>
<style>
    .pay-amount-input::-webkit-outer-spin-button,
    .pay-amount-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .pay-amount-input { -moz-appearance: textfield; appearance: textfield; }
</style>
@endsection
