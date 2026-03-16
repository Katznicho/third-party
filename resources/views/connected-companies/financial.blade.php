@extends('layouts.dashboard')

@section('title', 'Provider Financial Summary')
@section('page-title', 'Provider Financial Summary')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Financial Summary</h1>
            <p class="text-slate-600 mt-1 text-sm">
                {{ $connection->connected_business_name ?? 'Service Provider' }} — all guarantees and client shares for this service provider.
            </p>
        </div>
        <a href="{{ route('connected-companies.show', $connection->id) }}"
           class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm">
            ← Back to Provider Details
        </a>
    </div>

    <!-- Provider information card (similar to vendor balance statement) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-medium text-slate-900">
                    {{ $connection->connected_business_name ?? 'Service Provider' }}
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Code:
                    <span class="inline-flex items-center">
                        <code class="bg-slate-100 px-2 py-1 rounded text-xs font-mono">
                            {{ $connection->connectedBusiness->code ?? 'N/A' }}
                        </code>
                    </span>
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    Linked to {{ $insuranceCompany->name }} as a service provider.
                </p>
            </div>
            <div class="text-right space-y-2 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Connection Date</p>
                    <p class="text-sm font-medium text-slate-900">
                        {{ $connection->created_at?->format('M d, Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Total invoices (approved amounts)</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">
                        UGX {{ number_format($totalApproved ?? 0, 2) }}
                    </p>
                </div>
                <div class="p-3 bg-slate-100 rounded-lg">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Authorized transactions (guaranteed amounts)</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">
                        UGX {{ number_format($totalGuaranteed ?? 0, 2) }}
                    </p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Client share at this provider</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">
                        UGX {{ number_format($totalClientPortion ?? 0, 2) }}
                    </p>
                </div>
                <div class="p-3 bg-amber-50 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600">Excluded items (not covered)</p>
                    <p class="text-2xl font-bold text-red-700 mt-1">
                        UGX {{ number_format($totalExcluded ?? 0, 2) }}
                    </p>
                </div>
                <div class="p-3 bg-red-50 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mt-4">
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Invoice Number</label>
                    <input type="text" name="invoice_number" value="{{ request('invoice_number') }}"
                           class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Search by invoice number">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending_review" {{ request('status') === 'pending_review' ? 'selected' : '' }}>Pending review</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From date</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">To date</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="to" value="{{ request('to') }}"
                               class="w-full px-3 py-2 border rounded-md text-sm border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="px-6 py-4">
            @if($authorizations->isEmpty())
                <p class="text-sm text-slate-500">
                    No authorizations found for this provider with the current filters.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Requested</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Authorization Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Total (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Insurance (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Client (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Excluded (UGX)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($authorizations as $auth)
                            @php $breakdown = $auth->breakdown ?? []; @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $auth->requested_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-slate-900">
                                    {{ $auth->authorization_reference ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $auth->external_invoice_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-900">
                                    UGX {{ number_format($auth->total_amount ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-emerald-700">
                                    UGX {{ number_format($auth->insurance_total ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-amber-700">
                                    UGX {{ number_format($auth->client_total ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-red-700">
                                    UGX {{ number_format($breakdown['excluded'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $auth->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $auth->status === 'pending_review' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $auth->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $auth->status ?? 'N/A')) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $authorizations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

