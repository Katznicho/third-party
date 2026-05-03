@extends('layouts.dashboard')

@section('title', 'Clients')
@section('page-title', 'Clients')

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
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Clients</h1>
            <p class="text-slate-600 mt-1">Manage insurance clients</p>
        </div>
        @if(!($insuranceCompany->open_enrollment_enabled ?? false))
        <a href="{{ route('clients.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 text-center sm:text-left">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Client
        </a>
        @endif
    </div>

    <!-- Clients List -->
    <div class="mt-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form method="get" action="{{ route('clients.index') }}" class="flex flex-wrap items-center gap-2 flex-1 max-w-2xl">
                <label for="clients-search" class="sr-only">Search clients</label>
                <input
                    type="search"
                    name="search"
                    id="clients-search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search name, ID, phone, email, policy #, plan…"
                    class="flex-1 min-w-[200px] px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 whitespace-nowrap">
                    Search
                </button>
                @if(($search ?? '') !== '')
                    <a href="{{ route('clients.index') }}" class="text-sm text-slate-600 hover:text-slate-900 whitespace-nowrap">Clear</a>
                @endif
            </form>
            @if($clients->total() > 0)
                <p class="text-xs text-slate-500 sm:text-right shrink-0">
                    Showing {{ $clients->firstItem() }}–{{ $clients->lastItem() }} of {{ $clients->total() }}
                </p>
            @endif
        </div>
        @if($clients->count() > 0)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID/Passport</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($clients as $client)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900">{{ $client->full_name }}</div>
                                <div class="text-sm text-slate-500">{{ ucfirst($client->type ?? 'N/A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($client->plan)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ $client->plan->name }}</span>
                                @else
                                    <span class="text-sm text-slate-400">No plan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $client->id_passport_no ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $client->cell_phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                <a href="{{ route('clients.edit', $client) }}" class="text-slate-600 hover:text-slate-900 mr-3">Edit</a>
                                <a href="{{ route('clients.account-statement', $client) }}" class="text-green-600 hover:text-green-900">Account Statement</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($clients->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $clients->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                @if(($search ?? '') !== '')
                    <h3 class="mt-2 text-sm font-medium text-slate-900">No matching clients</h3>
                    <p class="mt-1 text-sm text-slate-500">Try a different search or <a href="{{ route('clients.index') }}" class="text-blue-600 hover:underline">clear the filter</a>.</p>
                @else
                    <h3 class="mt-2 text-sm font-medium text-slate-900">No clients</h3>
                    @if($insuranceCompany->open_enrollment_enabled ?? false)
                        <p class="mt-1 text-sm text-slate-500">Clients enroll directly — no manual registration needed.</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">Get started by creating a new client.</p>
                        <div class="mt-6">
                            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                New Client
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
    </div>
</div>
@endsection
