@extends('layouts.dashboard')

@section('title', 'Record client portion payment')
@section('page-title', 'Record client portion')

@section('content')
<div class="space-y-6 max-w-xl">
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Record client portion collected</h2>
        <p class="text-slate-600 text-sm mb-6">
            Use this after you have collected the client portion (e.g. UGX 120,103) via mobile money. The record will appear in <strong>Payments</strong>, the <strong>client account statement</strong>, <strong>Balance statement</strong>, and <strong>Third-party vendor</strong> page.
        </p>

        <form action="{{ route('payments.record-client-portion.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="policy_number" class="block text-sm font-medium text-slate-700 mb-1">Policy number <span class="text-red-500">*</span></label>
                <input type="text" name="policy_number" id="policy_number" value="{{ old('policy_number') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. POL-2024-001">
                @error('policy_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">Amount collected (UGX) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. 120103">
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mobile_money_number" class="block text-sm font-medium text-slate-700 mb-1">Mobile money number (optional)</label>
                <input type="text" name="mobile_money_number" id="mobile_money_number" value="{{ old('mobile_money_number') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. +256759983853">
                @error('mobile_money_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Record payment
                </button>
                <a href="{{ route('payments.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <p class="text-sm text-slate-500">
        After recording, the payment will show in: <a href="{{ route('payments.index') }}" class="text-blue-600 hover:underline">Payments</a>,
        the client’s <a href="{{ route('clients.index') }}" class="text-blue-600 hover:underline">account statement</a>,
        <a href="{{ route('connected-companies.index') }}" class="text-blue-600 hover:underline">third-party vendor</a> page, and balance statement.
    </p>
</div>
@endsection
