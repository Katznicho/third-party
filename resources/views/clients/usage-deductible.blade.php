@extends('layouts.dashboard')

@section('title', 'Deductible Usage')
@section('page-title', 'Deductible Usage')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Deductible Usage</h1>
            <p class="text-slate-600 mt-1">
                {{ $client->full_name }} — Total deductible used:
                <span class="font-semibold">UGX {{ number_format($totalMetric, 2) }}</span>
            </p>
        </div>
        <a href="{{ route('clients.account-statement', $client) }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
            ← Back to Account Statement
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50">
            <form method="GET" class="flex items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by invoice #..."
                    class="w-64 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button type="submit" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ request()->url() }}" class="text-xs text-slate-500 hover:text-slate-700 ml-2">Clear</a>
                @endif
            </form>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100">
                Export PDF
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Requested</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Deductible (UGX)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($authorizations as $auth)
                        @php $breakdown = $auth->breakdown ?? []; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $auth->requested_at?->format('d M Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-800">{{ $auth->external_invoice_number ?? '–' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-amber-700">{{ number_format($breakdown['deductible'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                No deductible usage found for this client.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($authorizations instanceof \Illuminate\Pagination\LengthAwarePaginator && $authorizations->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $authorizations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

