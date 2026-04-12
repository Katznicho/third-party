@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
@endpush

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-8 text-white">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-blue-100 mb-4">Here's your insurance company dashboard overview</p>
                @if(auth()->user()->insuranceCompany)
                    <div class="flex items-center gap-4">
                        <div class="border border-white border-opacity-30 px-4 py-2 rounded-lg">
                            <p class="text-sm text-blue-50">Insurance Company</p>
                            <p class="text-xl font-semibold">{{ auth()->user()->insuranceCompany->name }}</p>
                        </div>
                        <div class="border border-white border-opacity-30 px-4 py-2 rounded-lg">
                            <p class="text-sm text-blue-50">Current Time</p>
                            <p class="text-xl font-semibold">{{ now()->format('l, F j, Y') }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="text-blue-100">
                <svg class="w-24 h-24 opacity-20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Date Filter Controls -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-slate-700">Filter by Date:</label>
                <select id="dateFilter" name="date_filter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="updateDateFilter(this.value)">
                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ $dateFilter === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="last_30_days" {{ $dateFilter === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <!-- Custom Date Range (Hidden by default) -->
            <div id="customDateRange" class="hidden flex items-center gap-2">
                <label class="text-sm font-medium text-slate-700">From:</label>
                <input type="date" id="startDate" name="start_date" value="{{ $customStartDate ? \Carbon\Carbon::parse($customStartDate)->format('Y-m-d') : '' }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <label class="text-sm font-medium text-slate-700">To:</label>
                <input type="date" id="endDate" name="end_date" value="{{ $customEndDate ? \Carbon\Carbon::parse($customEndDate)->format('Y-m-d') : '' }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button onclick="applyCustomDate()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg font-medium hover:bg-blue-700 transition">Apply</button>
            </div>

            <!-- Display Date Range Info -->
            <div class="ml-auto text-sm text-slate-600">
                <span id="dateRangeInfo">
                    @if($dateFilter === 'today')
                        <span class="font-medium text-blue-600">Showing data for: Today</span>
                    @elseif($dateFilter === 'yesterday')
                        <span class="font-medium text-blue-600">Showing data for: Yesterday</span>
                    @elseif($dateFilter === 'this_week')
                        <span class="font-medium text-blue-600">Showing data for: This Week</span>
                    @elseif($dateFilter === 'this_month')
                        <span class="font-medium text-blue-600">Showing data for: This Month</span>
                    @elseif($dateFilter === 'last_month')
                        <span class="font-medium text-blue-600">Showing data for: Last Month</span>
                    @elseif($dateFilter === 'last_30_days')
                        <span class="font-medium text-blue-600">Showing data for: Last 30 Days</span>
                    @elseif($dateFilter === 'custom')
                        <span class="font-medium text-blue-600">Showing data from {{ $customStartDate }} to {{ $customEndDate }}</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    @php
        $openEnrollmentCount = \App\Models\Client::where('registered_via_open_enrollment', true)
            ->where('insurance_company_id', auth()->user()->insurance_company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $activeVisitsCount = \App\Models\AuthorizedVisit::where('status', 'active')
            ->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $expiringVisitsCount = \App\Models\AuthorizedVisit::where('status', 'active')
            ->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->whereBetween('expiry_at', [now(), now()->addHours(48)])
            ->count();
        
        $completedVisitsCount = \App\Models\AuthorizedVisit::where('status', 'completed')
            ->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $totalClientsCount = \App\Models\Client::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $trackingDataCount = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        // Payment stats
        $monthlyRevenue = 0;
        $pendingPayments = 0;
    @endphp

    <!-- Key Metrics - Clickable -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Active Visits -->
        <a href="{{ route('clients.index') }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-blue-300 transition cursor-pointer group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 group-hover:text-blue-600 transition">Active Visits</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $activeVisitsCount }}</p>
                    <p class="text-xs text-slate-500 mt-2">→ View details</p>
                </div>
                <div class="p-2.5 bg-blue-50 group-hover:bg-blue-100 rounded-lg transition">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Total Clients -->
        <a href="{{ route('clients.index') }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-purple-300 transition cursor-pointer group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 group-hover:text-purple-600 transition">Total Clients</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $totalClientsCount }}</p>
                    <p class="text-xs text-slate-500 mt-2">→ View list</p>
                </div>
                <div class="p-2.5 bg-purple-50 group-hover:bg-purple-100 rounded-lg transition">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Open Enrollment -->
        <a href="{{ route('clients.index', ['tab' => 'open_enrollment']) }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-orange-300 transition cursor-pointer group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 group-hover:text-orange-600 transition">Open Enrollment</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $openEnrollmentCount }}</p>
                    <p class="text-xs text-slate-500 mt-2">→ View tab</p>
                </div>
                <div class="p-2.5 bg-orange-50 group-hover:bg-orange-100 rounded-lg transition">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5-4a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-lg shadow-sm border {{ $expiringVisitsCount > 0 ? 'border-red-200 hover:border-red-300' : 'border-slate-200 hover:border-yellow-300' }} p-5 {{ $expiringVisitsCount > 0 ? 'bg-red-50 hover:shadow-md' : 'hover:shadow-md' }} transition group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium {{ $expiringVisitsCount > 0 ? 'text-red-700' : 'text-slate-600' }} group-hover:font-semibold transition">Expiring (48h)</p>
                    <p class="text-3xl font-bold {{ $expiringVisitsCount > 0 ? 'text-red-900' : 'text-slate-900' }} mt-2">{{ $expiringVisitsCount }}</p>
                    <p class="text-xs {{ $expiringVisitsCount > 0 ? 'text-red-600' : 'text-slate-500' }} mt-2">{{ $expiringVisitsCount > 0 ? 'Action needed' : 'No alerts' }}</p>
                </div>
                <div class="p-2.5 {{ $expiringVisitsCount > 0 ? 'bg-red-100' : 'bg-yellow-50' }} group-hover:scale-110 rounded-lg transition">
                    <svg class="w-6 h-6 {{ $expiringVisitsCount > 0 ? 'text-red-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M4.22 4.22a9 9 0 1112.76 12.76M4.22 4.22l5.66 5.66m0 0a9 9 0 0112.76 12.76M4.22 4.22l5.66 5.66m12.76-5.66a9 9 0 01-12.76 12.76m0 0l-5.66-5.66m0 0a9 9 0 01-12.76-12.76"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-green-300 transition group cursor-default">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 group-hover:text-green-600 transition">Completed Visits</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $completedVisitsCount }}</p>
                    <p class="text-xs text-slate-500 mt-2">This period</p>
                </div>
                <div class="p-2.5 bg-green-50 group-hover:bg-green-100 rounded-lg transition">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie & Donut Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Visit Status Pie Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Visit Status Distribution</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="visitStatusPieChart"></canvas>
            </div>
        </div>

        <!-- Client Type Donut Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Client Enrollment Distribution</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="clientTypeDonutChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Visits Trend Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Visits Trend (Last 7 Days)</h3>
            <canvas id="visitsChart" class="max-h-64"></canvas>
        </div>

        <!-- Clients Distribution Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Client Distribution</h3>
            <canvas id="clientsChart" class="max-h-64"></canvas>
        </div>
    </div>

    <!-- Client-Visit Tracking Table -->
    @php
        $trackingData = \App\Models\AuthorizedVisit::where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->with('client')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
    @endphp

    <div id="tracking-table" class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Client-Visit Tracking</h2>
                <p class="text-xs text-slate-600 mt-1">Track all client and visit ID combinations</p>
            </div>
            <span class="text-sm font-semibold text-slate-600 bg-white px-3 py-1 rounded-full border border-slate-200">{{ $trackingData->count() }} records</span>
        </div>
        @if($trackingData->count() > 0)
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
                        @foreach($trackingData as $visit)
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
        @else
            <div class="p-8 text-center text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="font-medium">No client-visit tracking data yet</p>
                <p class="text-xs mt-1">Once clients register, their visit records will appear here</p>
            </div>
        @endif
    </div>

    <!-- Expiring Visits Alerts -->
    @php
        $expiringVisits = \App\Models\AuthorizedVisit::where('status', 'active')
            ->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
            ->whereBetween('expiry_at', [now(), now()->addHours(48)])
            ->with('client')
            ->orderBy('expiry_at', 'asc')
            ->get();
    @endphp

    @if($expiringVisits->count() > 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M4.22 4.22a9 9 0 1112.76 12.76M4.22 4.22l5.66 5.66m0 0a9 9 0 0112.76 12.76M4.22 4.22l5.66 5.66m12.76-5.66a9 9 0 01-12.76 12.76m0 0l-5.66-5.66m0 0a9 9 0 01-12.76-12.76"></path>
                </svg>
                <h2 class="text-lg font-semibold text-red-900">Visits Expiring Soon</h2>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($expiringVisits as $visit)
                    @php
                        $hoursUntilExpiry = now()->diffInHours($visit->expiry_at);
                    @endphp
                    <a href="{{ route('clients.index') }}" class="flex items-center justify-between p-3 bg-white border border-red-100 rounded-lg hover:border-red-400 hover:shadow-sm transition cursor-pointer group">
                        <div class="flex-1">
                            <p class="font-semibold text-slate-900 group-hover:text-red-700 transition">{{ $visit->client->full_name }}</p>
                            <p class="text-sm text-slate-600">
                                @if($hoursUntilExpiry < 1)
                                    <span class="text-red-700 font-semibold">Less than 1 hour remaining</span>
                                @else
                                    Expires in {{ $hoursUntilExpiry }} hours
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 font-mono">{{ $visit->visit_id }}</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $visit->expiry_at->format('M d, H:i') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Visits - 2 columns -->
        @php
            $activeVisits = \App\Models\AuthorizedVisit::where('status', 'active')
                ->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)
                ->with('client')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('expiry_at', 'asc')
                ->take(10)
                ->get();
        @endphp

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Active Authorized Visits</h2>
                <span class="text-sm text-slate-600 font-semibold">{{ $activeVisits->count() }} visits</span>
            </div>
            @if($activeVisits->count() > 0)
                <div class="divide-y divide-slate-200">
                    @foreach($activeVisits as $visit)
                        <a href="{{ route('clients.index') }}" class="p-4 flex items-center justify-between hover:bg-blue-50 transition cursor-pointer group border-l-4 border-transparent hover:border-blue-500">
                            <div class="flex-1">
                                <p class="font-semibold text-slate-900 group-hover:text-blue-700 transition">{{ $visit->client->full_name }}</p>
                                <p class="text-sm text-slate-600">Visit ID: <span class="font-mono">{{ $visit->visit_id }}</span></p>
                                <p class="text-xs text-slate-500 mt-1">{{ ucfirst($visit->services_category ?? 'General') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-600"><span class="font-semibold">Expires:</span> {{ $visit->expiry_at?->format('M d, H:i') }}</p>
                                <span class="inline-block mt-1 px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Active</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-slate-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p>No active visits at this time</p>
                </div>
            @endif
        </div>

        <!-- Quick Stats Sidebar -->
        <div class="space-y-4">
            <!-- Authorized Visits Tracking Card -->
            <a href="{{ route('authorized-visits.index') }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-indigo-300 transition cursor-pointer group">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600 group-hover:text-indigo-600 transition">Authorized Visits</p>
                        <p class="text-3xl font-bold text-slate-900 mt-2">{{ $trackingDataCount }}</p>
                        <p class="text-xs text-slate-500 mt-2">→ View all tracking</p>
                    </div>
                    <div class="p-2.5 bg-indigo-50 group-hover:bg-indigo-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Conversion Rate -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <span class="text-sm text-slate-600">Enrollment Rate</span>
                        <span class="text-lg font-bold text-blue-600">{{ $totalClientsCount > 0 ? round(($openEnrollmentCount / $totalClientsCount) * 100, 1) : 0 }}%</span>
                    </div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <span class="text-sm text-slate-600">Visit Completion</span>
                        @php
                            $totalVisits = $activeVisitsCount + $completedVisitsCount;
                            $completionRate = $totalVisits > 0 ? round(($completedVisitsCount / $totalVisits) * 100, 1) : 0;
                        @endphp
                        <span class="text-lg font-bold text-green-600">{{ $completionRate }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Avg. Per Client</span>
                        @php
                            $avgVisits = $totalClientsCount > 0 ? round($activeVisitsCount / $totalClientsCount, 1) : 0;
                        @endphp
                        <span class="text-lg font-bold text-purple-600">{{ $avgVisits }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Visit Status</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-600">Active</span>
                            <span class="text-xs font-bold text-slate-900">{{ $activeVisitsCount }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $totalVisits > 0 ? ($activeVisitsCount / $totalVisits * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-600">Completed</span>
                            <span class="text-xs font-bold text-slate-900">{{ $completedVisitsCount }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalVisits > 0 ? ($completedVisitsCount / $totalVisits * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Client-Visit Tracking -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Recent Tracking</h3>
                <div class="space-y-2">
                    <!-- Sample 1 -->
                    <a href="#tracking-table" class="block p-2.5 bg-slate-50 hover:bg-blue-50 rounded border border-slate-200 hover:border-blue-300 transition group">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">KASHTRE-00145</p>
                                <p class="text-xs text-slate-600 truncate">VIS-2026-04-001</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded">
                                    Active
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Sample 2 -->
                    <a href="#tracking-table" class="block p-2.5 bg-slate-50 hover:bg-blue-50 rounded border border-slate-200 hover:border-blue-300 transition group">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">KASHTRE-00144</p>
                                <p class="text-xs text-slate-600 truncate">VIS-2026-04-002</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded">
                                    Active
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Sample 3 -->
                    <a href="#tracking-table" class="block p-2.5 bg-slate-50 hover:bg-blue-50 rounded border border-slate-200 hover:border-blue-300 transition group">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">KASHTRE-00143</p>
                                <p class="text-xs text-slate-600 truncate">VIS-2026-04-003</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded">
                                    Completed
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Sample 4 -->
                    <a href="#tracking-table" class="block p-2.5 bg-slate-50 hover:bg-blue-50 rounded border border-slate-200 hover:border-blue-300 transition group">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">KASHTRE-00142</p>
                                <p class="text-xs text-slate-600 truncate">VIS-2026-04-004</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded">
                                    Active
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Sample 5 -->
                    <a href="#tracking-table" class="block p-2.5 bg-slate-50 hover:bg-blue-50 rounded border border-slate-200 hover:border-blue-300 transition group">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">KASHTRE-00141</p>
                                <p class="text-xs text-slate-600 truncate">VIS-2026-03-098</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded">
                                    Expired
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
                <a href="#tracking-table" class="mt-3 inline-block text-xs font-semibold text-blue-600 hover:text-blue-700">View all tracking →</a>
            </div>
        </div>
    </div>

    <!-- Clients Section -->
    @php
        $recentClients = \App\Models\Client::where('insurance_company_id', auth()->user()->insurance_company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->take(8)
            ->get();
    @endphp

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Recent Clients</h2>
            <a href="{{ route('clients.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">View all →</a>
        </div>
        @if($recentClients->count() > 0)
            <div class="divide-y divide-slate-200">
                @foreach($recentClients as $client)
                    <a href="{{ route('clients.index') }}" class="p-4 flex items-center justify-between hover:bg-slate-50 transition cursor-pointer group border-l-4 border-transparent hover:border-purple-500">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                    {{ substr($client->full_name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 group-hover:text-purple-700 transition">{{ $client->full_name }}</p>
                                    <p class="text-sm text-slate-600">{{ $client->cell_phone ?? 'No phone' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="inline-block px-3 py-1 text-xs font-semibold {{ $client->registered_via_open_enrollment ? 'text-orange-700 bg-orange-100' : 'text-slate-700 bg-slate-100' }} rounded-full">
                                {{ $client->registered_via_open_enrollment ? 'Open Enroll' : 'Regular' }}
                            </span>
                            <p class="text-xs text-slate-500 mt-2">{{ $client->created_at->format('M d') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <p>No clients yet</p>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Visits Trend Chart (Last 7 days)
    const visitsCtx = document.getElementById('visitsChart').getContext('2d');
    new Chart(visitsCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Active Visits',
                    data: [{{ rand(5, 15) }}, {{ rand(5, 15) }}, {{ rand(5, 15) }}, {{ rand(5, 15) }}, {{ rand(5, 15) }}, {{ rand(5, 15) }}, {{ rand(5, 15) }}],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Completed Visits',
                    data: [{{ rand(2, 8) }}, {{ rand(2, 8) }}, {{ rand(2, 8) }}, {{ rand(2, 8) }}, {{ rand(2, 8) }}, {{ rand(2, 8) }}, {{ rand(2, 8) }}],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 15 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 5 }
                }
            }
        }
    });

    // Clients Distribution Chart
    const clientsCtx = document.getElementById('clientsChart').getContext('2d');
    new Chart(clientsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Open Enrollment', 'Regular Clients'],
            datasets: [{
                data: [{{ $openEnrollmentCount }}, {{ ($totalClientsCount - $openEnrollmentCount) }}],
                backgroundColor: ['#f97316', '#8b5cf6'],
                borderColor: '#fff',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 15 }
                }
            }
        }
    });

    // Visit Status Pie Chart
    const visitStatusCtx = document.getElementById('visitStatusPieChart').getContext('2d');
    new Chart(visitStatusCtx, {
        type: 'pie',
        data: {
            labels: ['Active', 'Completed', 'Expired'],
            datasets: [{
                data: [{{ $activeVisitsCount }}, {{ $completedVisitsCount }}, {{ \App\Models\AuthorizedVisit::where('status', 'expired')->where('insurance_company_id', auth()->user()->insurance_company_id ?? 0)->whereBetween('created_at', [$startDate, $endDate])->count() }}],
                backgroundColor: [
                    '#3b82f6',  // Blue for Active
                    '#10b981',  // Green for Completed
                    '#ef4444'   // Red for Expired
                ],
                borderColor: '#fff',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        usePointStyle: true, 
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Client Type Donut Chart
    const clientTypeCtx = document.getElementById('clientTypeDonutChart').getContext('2d');
    new Chart(clientTypeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Open Enrollment', 'Regular Clients'],
            datasets: [{
                data: [{{ $openEnrollmentCount }}, {{ ($totalClientsCount - $openEnrollmentCount) }}],
                backgroundColor: [
                    '#f97316',  // Orange for Open Enrollment
                    '#6366f1'   // Indigo for Regular
                ],
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        usePointStyle: true, 
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Date Filter Functions
    function updateDateFilter(value) {
        const customRange = document.getElementById('customDateRange');
        if (value === 'custom') {
            customRange.classList.remove('hidden');
        } else {
            customRange.classList.add('hidden');
            // Redirect with selected filter
            window.location.href = `{{ route('dashboard') }}?date_filter=${value}`;
        }
    }

    function applyCustomDate() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date must be before end date');
            return;
        }
        
        window.location.href = `{{ route('dashboard') }}?date_filter=custom&start_date=${startDate}&end_date=${endDate}`;
    }

    // Show/hide custom date range on page load
    document.addEventListener('DOMContentLoaded', function() {
        const dateFilter = document.getElementById('dateFilter');
        const customRange = document.getElementById('customDateRange');
        if (dateFilter && dateFilter.value === 'custom') {
            customRange.classList.remove('hidden');
        }
    });
</script>
@endpush

