@extends('layouts.dashboard')

@section('title', 'Service Providers')
@section('page-title', 'Service Providers')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Service Providers</h1>
            <p class="text-slate-600 mt-1">Service providers connected to {{ $insuranceCompany->name }}</p>
        </div>
    </div>

    <!-- Service Providers -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($connections->count() > 0)
            <div class="divide-y divide-slate-200">
                @foreach($connections as $connection)
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-slate-900 truncate" title="{{ $connection->connected_business_name ?? 'Kashtre Business' }}">
                                    {{ $connection->connected_business_name ?? 'Kashtre Business' }}
                                </h3>
                                @if($connection->isActive())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        ✓ Active
                                    </span>
                                @elseif($connection->isSuspended())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-100">
                                        ⊘ Suspended
                                    </span>
                                @elseif($connection->isBlocked())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                        ✕ Blocked
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Connected {{ $connection->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('connected-companies.show', $connection->id) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-slate-300 text-xs font-medium rounded-md text-slate-700 hover:bg-slate-50">
                                View details
                            </a>
                            <a href="{{ route('third-party-vendors.show', $connection->connected_business_id ?? $connection->id) }}"
                               class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                View payments →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <h3 class="text-lg font-medium text-slate-900 mb-2">No service providers yet</h3>
                <p class="text-slate-500">Companies will appear here once they connect to your insurance company.</p>
            </div>
        @endif
    </div>
</div>
@endsection
