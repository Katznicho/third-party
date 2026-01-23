<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - Kashtre</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-slate-800 border-r border-slate-700">
            <!-- Logo -->
            <div class="flex flex-col items-center justify-center min-h-20 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700">
                <img src="{{ asset('images/logo.svg') }}" alt="Kashtre Logo" class="h-10 w-auto mb-2 object-contain">
                <h1 class="text-lg font-bold text-white">Kashtre</h1>
                @if(auth()->user()->insuranceCompany)
                    <p class="text-xs text-blue-100 mt-1">{{ auth()->user()->insuranceCompany->name }}</p>
                    <p class="text-xs font-mono font-semibold text-blue-200 mt-0.5">Code: {{ auth()->user()->insuranceCompany->code ?? 'N/A' }}</p>
                @endif
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <!-- Dashboard -->
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <!-- Users -->
                <a 
                    href="{{ route('users.index') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('users.*') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Users
                </a>

                <!-- Clients -->
                <a 
                    href="{{ route('clients.index') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('clients.*') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Clients
                </a>

                <!-- Plans -->
                <a 
                    href="{{ route('plans.index') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('plans.*') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Plans
                </a>

                <!-- Payments -->
                <a 
                    href="{{ route('payments.index') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('payments.*') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Payments
                </a>

                <!-- Invoices -->
                <a 
                    href="{{ route('invoices.index') }}" 
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-150 {{ request()->routeIs('invoices.*') ? 'bg-blue-900/20 text-blue-400' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                    </svg>
                    Invoices
                </a>
            </nav>

            <!-- Connected Companies Section -->
            @if(auth()->user()->insuranceCompany)
                @php
                    $connections = auth()->user()->insuranceCompany->connectedCompanies;
                @endphp
                @if($connections->count() > 0)
                    <div class="px-4 py-4 border-t border-slate-700">
                        <div class="mb-3">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Connected Companies
                            </h3>
                        </div>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($connections as $connection)
                                @if($connection->connectedBusiness)
                                    <div class="bg-slate-700/50 rounded-lg p-3 hover:bg-slate-700 transition duration-150 border border-slate-600/50">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-slate-200 truncate" title="{{ $connection->connectedBusiness->name }}">
                                                    {{ $connection->connectedBusiness->name }}
                                                </p>
                                                <div class="mt-1.5 flex items-center space-x-2">
                                                    <span class="text-xs text-slate-400">Code:</span>
                                                    <span class="text-xs font-mono font-semibold text-blue-300 bg-blue-900/30 px-2 py-0.5 rounded">
                                                        {{ $connection->connectedBusiness->code }}
                                                    </span>
                                                </div>
                                                @if($connection->connectedBusiness->email)
                                                    <p class="text-xs text-slate-500 mt-1 truncate" title="{{ $connection->connectedBusiness->email }}">
                                                        {{ $connection->connectedBusiness->email }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="px-4 py-4 border-t border-slate-700">
                        <div class="mb-3">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Connected Companies
                            </h3>
                        </div>
                        <div class="bg-slate-700/30 rounded-lg p-3 border border-slate-600/30">
                            <p class="text-xs text-slate-400 text-center">No connected companies yet</p>
                        </div>
                    </div>
                @endif
            @endif

            <!-- User Section -->
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400">{{ auth()->user()->username }}</p>
                        @if(auth()->user()->insuranceCompany)
                            <p class="text-xs text-blue-400 font-medium truncate" title="{{ auth()->user()->insuranceCompany->name }}">{{ auth()->user()->insuranceCompany->name }}</p>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button 
                        type="submit"
                        class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-400 bg-red-900/20 rounded-lg hover:bg-red-900/30 transition duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Mobile Sidebar -->
        <div class="lg:hidden fixed inset-0 z-40 bg-black bg-opacity-50 hidden" id="mobile-sidebar-backdrop"></div>
        <aside class="lg:hidden fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 border-r border-slate-700 transform -translate-x-full transition-transform duration-300 ease-in-out" id="mobile-sidebar">
            <!-- Mobile sidebar content (same as desktop) -->
            <div class="flex items-center justify-between min-h-20 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex-1 flex flex-col items-center">
                    <img src="{{ asset('images/logo.svg') }}" alt="Kashtre Logo" class="h-10 w-auto mb-1 object-contain">
                    <h1 class="text-lg font-bold text-white">Kashtre</h1>
                    @if(auth()->user()->insuranceCompany)
                        <p class="text-xs text-blue-100">{{ auth()->user()->insuranceCompany->name }}</p>
                        <p class="text-xs text-blue-200">{{ auth()->user()->insuranceCompany->code }}</p>
                    @endif
                </div>
                <button onclick="toggleMobileSidebar()" class="text-white hover:text-slate-200 ml-2 absolute top-2 right-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Navigation items same as desktop -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Users
                </a>
                <a href="{{ route('clients.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Clients
                </a>
                <a href="{{ route('plans.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Plans
                </a>
                <a href="{{ route('payments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Payments
                </a>
                <a href="{{ route('invoices.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-700 transition duration-150">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                    </svg>
                    Invoices
                </a>
            </nav>
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ auth()->user()->username }}</p>
                        @if(auth()->user()->insuranceCompany)
                            <p class="text-xs text-blue-400 font-medium break-words leading-tight mt-1">{{ auth()->user()->insuranceCompany->name }}</p>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-400 bg-red-900/20 rounded-lg hover:bg-red-900/30 transition duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-6">
                <div class="flex items-center">
                    <button 
                        onclick="toggleMobileSidebar()"
                        class="lg:hidden p-2 rounded-md text-slate-600 hover:bg-slate-100"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="ml-4 lg:ml-0 text-lg font-semibold text-slate-900">@yield('page-title', 'Dashboard')</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notifications or other header items can go here -->
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-slate-50">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const backdrop = document.getElementById('mobile-sidebar-backdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        // Close mobile sidebar when clicking backdrop
        document.getElementById('mobile-sidebar-backdrop').addEventListener('click', toggleMobileSidebar);
    </script>
</body>
</html>
