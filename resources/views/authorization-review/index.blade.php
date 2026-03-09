@extends('layouts.dashboard')

@section('title', 'Authorization Review')
@section('page-title', 'Authorization Review')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manual clearance</h1>
            <p class="text-slate-600 mt-1">Pre-authorizations pending manual clearance</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <!-- Status tabs -->
    <div class="flex space-x-1 bg-slate-100 rounded-lg p-1">
        <a href="{{ route('authorization-review.index', ['status' => 'pending']) }}"
           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
            Pending <span class="ml-1 text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('authorization-review.index', ['status' => 'approved']) }}"
           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'approved' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
            Approved <span class="ml-1 text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full">{{ $counts['approved'] }}</span>
        </a>
        <a href="{{ route('authorization-review.index', ['status' => 'rejected']) }}"
           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'rejected' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
            Rejected <span class="ml-1 text-xs bg-red-100 text-red-800 px-1.5 py-0.5 rounded-full">{{ $counts['rejected'] }}</span>
        </a>
        <a href="{{ route('authorization-review.index', ['status' => 'all']) }}"
           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
            All
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('authorization-review.index') }}" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by auth #, client, or policy…"
               class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">Search</button>
        @if($search)
            <a href="{{ route('authorization-review.index', ['status' => $status]) }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-sm rounded-lg hover:bg-slate-300 transition">Clear</a>
        @endif
    </form>

    <!-- Approval levels & approvers -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700">Approval workflow</h2>
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{ $totalLevels }} {{ $totalLevels === 1 ? 'level' : 'levels' }}</span>
        </div>
        <div class="grid grid-cols-1 {{ $totalLevels >= 2 ? 'md:grid-cols-' . $totalLevels : '' }} gap-3">
            @for($lvl = 1; $lvl <= $totalLevels; $lvl++)
                <div class="border border-slate-200 rounded-lg p-3 {{ in_array($lvl, $userApproverLevels) ? 'bg-blue-50 border-blue-200' : 'bg-slate-50' }}">
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Level {{ $lvl }}
                        @if($lvl === 1) — First reviewer @elseif($lvl === 2) — Second reviewer @elseif($lvl === 3) — Final approver @endif
                    </p>
                    @if(isset($approversByLevel[$lvl]) && $approversByLevel[$lvl]->count() > 0)
                        <div class="space-y-1">
                            @foreach($approversByLevel[$lvl] as $approver)
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-600 text-xs font-bold">{{ strtoupper(substr($approver->user->name ?? '?', 0, 1)) }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">{{ $approver->user->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-slate-500">{{ $approver->user->email ?? '' }}</p>
                                    </div>
                                    @if($approver->user_id === auth()->id())
                                        <span class="text-xs bg-blue-600 text-white px-1.5 py-0.5 rounded">You</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No approvers assigned</p>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($flaggedPreAuthorizations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Authorization #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Policy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Service category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Requested (UGX)</th>
                            @if($totalLevels > 1)
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Approval progress</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($flaggedPreAuthorizations as $preAuth)
                            @php
                                $approvedLevels = $preAuth->authorizationApprovals->where('action', 'approved')->pluck('level')->toArray();
                                $rejectedLevel = $preAuth->authorizationApprovals->where('action', 'rejected')->first();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    @if($preAuth->request_date)
                                        {{ $preAuth->request_date->format('M d, Y') }}<br>
                                        <span class="text-slate-400">{{ $preAuth->request_date->format('H:i') }}</span>
                                    @else
                                        {{ $preAuth->created_at->format('M d, Y H:i') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">
                                    @php
                                        $invoiceNum = null;
                                        if (preg_match('/Invoice\s+(\S+)/', $preAuth->request_description ?? '', $_m)) {
                                            $invoiceNum = $_m[1];
                                        }
                                    @endphp
                                    {{ $invoiceNum ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-slate-900">{{ $preAuth->authorization_number ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $preAuth->policy->policy_number ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $preAuth->client->full_name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $preAuth->serviceCategory->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 text-right font-medium">
                                    {{ $preAuth->requested_amount ? number_format($preAuth->requested_amount, 0) : '—' }}
                                </td>
                                @if($totalLevels > 1)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center space-x-1">
                                            @for($l = 1; $l <= $totalLevels; $l++)
                                                @if(in_array($l, $approvedLevels))
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold" title="Level {{ $l }} approved">{{ $l }}</span>
                                                @elseif($rejectedLevel && $rejectedLevel->level == $l)
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold" title="Level {{ $l }} rejected">{{ $l }}</span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400 text-xs font-bold" title="Level {{ $l }} pending">{{ $l }}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        {{ $preAuth->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($preAuth->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($preAuth->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('authorization-review.show', $preAuth) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition text-xs font-semibold">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $flaggedPreAuthorizations->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No items found</h3>
                <p class="mt-1 text-sm text-slate-500">
                    @if($status === 'pending')
                        No pre-authorizations pending manual clearance.
                    @else
                        No pre-authorizations match this filter.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@endsection
