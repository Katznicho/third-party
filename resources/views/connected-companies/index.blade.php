@extends('layouts.dashboard')

@section('title', 'Connected Companies')
@section('page-title', 'Connected Companies')

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
            <h1 class="text-2xl font-bold text-slate-900">Connected Companies</h1>
            <p class="text-slate-600 mt-1">Companies connected to {{ $insuranceCompany->name }}</p>
        </div>
    </div>

    <!-- Connected Companies -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($connections->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                @foreach($connections as $connection)
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 hover:shadow-md transition duration-150">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-slate-900 truncate" title="{{ $connection->connected_business_name ?? 'Kashtre Business' }}">
                                {{ $connection->connected_business_name ?? 'Kashtre Business' }}
                            </h3>
                        </div>
                        <div class="pt-2 border-t border-slate-200">
                            <span class="text-xs text-slate-500">
                                Connected {{ $connection->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <h3 class="text-lg font-medium text-slate-900 mb-2">No connected companies yet</h3>
                <p class="text-slate-500">Companies will appear here once they connect to your insurance company.</p>
            </div>
        @endif
    </div>
</div>
@endsection
