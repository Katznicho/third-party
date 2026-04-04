@extends('layouts.dashboard')

@section('title', 'Authorized Visits Tracking')
@section('page-title', 'Authorized Visits Tracking')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Authorized Visits Tracking</h1>
            <p class="text-slate-600 mt-1">Complete history of all client-visit combinations</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Summary Cards -->
    @php
        $totalAuthorized = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)->count();
        $activeCount = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)->where('status', 'active')->count();
        $completedCount = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)->where('status', 'completed')->count();
        $expiredCount = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)->where('status', 'expired')->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-600">Total Records</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $totalAuthorized }}</p>
        </div>
        <!-- Active -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-600">Active</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $activeCount }}</p>
        </div>
        <!-- Completed -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-600">Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $completedCount }}</p>
        </div>
        <!-- Expired -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-600">Expired</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $expiredCount }}</p>
        </div>
    </div>

    <!-- Tracking Table -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 bg-slate-50 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">All Authorized Visits</h2>
            <p class="text-xs text-slate-600 mt-1">{{ $authorizedVisits->total() }} total records</p>
        </div>
        @if($authorizedVisits->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Kashtre Client ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Visit ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Client Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Visit Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700">Tracked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($authorizedVisits as $visit)
                            <tr class="hover:bg-slate-50 transition cursor-pointer group">
                                <td class="px-6 py-3">
                                    <span class="font-mono text-sm font-semibold text-blue-600 group-hover:text-blue-700">{{ $visit->kashtre_client_id ?? 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="font-mono text-sm font-semibold text-purple-600 group-hover:text-purple-700">{{ $visit->visit_id }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white text-xs font-bold mr-2 flex-shrink-0">
                                            {{ substr($visit->client->full_name, 0, 1) }}
                                        </div>
                                        <span class="text-sm font-medium text-slate-900">{{ $visit->client->full_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm text-slate-600">{{ ucfirst($visit->services_category ?? 'General') }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ 
                                        $visit->status === 'active' ? 'bg-blue-100 text-blue-800' :
                                        ($visit->status === 'completed' ? 'bg-green-100 text-green-800' :
                                        ($visit->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800'))
                                    }}">
                                        {{ ucfirst($visit->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm text-slate-600">{{ $visit->visit_date->format('M d, Y') }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm {{ $visit->expiry_at && $visit->expiry_at < now() ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                        {{ $visit->expiry_at?->format('M d, H:i') ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-xs text-slate-500">{{ $visit->created_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $authorizedVisits->links() }}
            </div>
        @else
            <div class="p-8 text-center text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="font-medium">No authorized visits yet</p>
                <p class="text-xs mt-1">Once clients register, their visit records will appear here</p>
            </div>
        @endif
    </div>
</div>
@endsection
