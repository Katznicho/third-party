@extends('layouts.dashboard')

@section('title', request('status') === 'rejected' ? 'Rejected items' : 'Authorization Codes')
@section('page-title', request('status') === 'rejected' ? 'Rejected items' : 'Authorization Codes')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ request('status') === 'rejected' ? 'Rejected items' : 'Authorization Codes' }}
            </h1>
            @if(request('status') === 'rejected')
                <p class="text-slate-600 mt-1">
                    All transactions where the insurer rejected items or the entire invoice. Click into a row to see the rejected items.
                </p>
            @else
                <p class="text-slate-600 mt-1">
                    Invoice authorizations from Kashtre (third parties can track authorization references and financial splits).
                </p>
            @endif
        </div>
        <div class="text-sm text-slate-500">
            {{ $authorizations->total() }} authorization(s)
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form method="get" action="{{ route('authorization-codes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-slate-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    placeholder="Reference, confirmation code, invoice #..."
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Filter</button>
                <a href="{{ route('authorization-codes.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Authorization Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Total (UGX)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Client (UGX)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Insurance (UGX)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Deductible</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Co‑pay</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Co‑insurance</th>
                        @if(request('status') === 'rejected')
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Rejected items</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Policy</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Requested</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Completed</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($authorizations as $auth)
                        @php
                            $breakdown = $auth->breakdown ?? [];
                            $meta = $auth->metadata ?? [];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-mono text-slate-900">{{ $auth->authorization_reference }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $auth->external_invoice_number ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-slate-900">{{ number_format($auth->total_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-slate-900">{{ number_format($auth->client_total ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-slate-900">{{ number_format($auth->insurance_total ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-slate-700">
                                UGX {{ number_format($breakdown['deductible'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-slate-700">
                                UGX {{ number_format($breakdown['copay'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-slate-700">
                                UGX {{ number_format($breakdown['coinsurance'] ?? 0, 2) }}
                            </td>
                            @if(request('status') === 'rejected')
                                @php
                                    $rejectedItems = $auth->rejectedItems ?? collect();
                                    $rejectedCount = $rejectedItems->count();
                                    $itemsToShow = $rejectedItems->take(3);
                                    $moreCount = max(0, $rejectedCount - 3);
                                @endphp
                                <td class="px-4 py-3 text-sm text-right">
                                    @if($rejectedCount === 0)
                                        <span class="text-slate-500">—</span>
                                    @else
                                        <div class="text-xs text-red-700 space-y-1">
                                            @foreach($itemsToShow as $item)
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0 text-left">
                                                        <div class="font-medium text-[12px]">{{ $item->item_name ?? '—' }}</div>
                                                        @if(!empty($item->item_code))
                                                            <div class="text-[11px] text-red-600">({{ $item->item_code }})</div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-shrink-0 text-right">
                                                        UGX {{ number_format((float) ($item->amount ?? 0), 2) }}
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if($moreCount > 0)
                                                <div class="text-[11px] text-red-600">
                                                    +{{ $moreCount }} more ({{ $rejectedCount }} total)
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $auth->policy?->policy_number ?? '–' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $auth->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $auth->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $auth->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($auth->status ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $auth->requested_at?->format('d M Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $auth->completed_at?->format('d M Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('authorization-codes.show', $auth) }}"
                                   class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">
                                    View
                                </a>
                            </td>
                        </tr>
                        {{-- Intentionally removed "How we got here" helper row --}}
                    @empty
                        <tr>
                            <td colspan="15" class="px-4 py-8 text-center text-slate-500">No authorization codes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($authorizations->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $authorizations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
