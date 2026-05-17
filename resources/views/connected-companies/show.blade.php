@extends('layouts.dashboard')

@section('title', 'Service Provider Details')
@section('page-title', 'Service Provider Details')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Back link -->
    <div>
        <a href="{{ route('connected-companies.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Service Providers
        </a>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $connection->connected_business_name ?? 'Kashtre Business' }}
            </h1>
            <p class="text-slate-600 mt-1 text-sm">
                Connected to {{ $insuranceCompany->name }} as a service provider.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="text-right">
                <p class="text-xs text-slate-500">Connection Date</p>
                <p class="text-sm font-medium text-slate-900">
                    {{ $connection->created_at->format('M d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Info + High-level status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Provider Overview</h2>
            </div>
            <div class="px-6 py-5 space-y-4 text-sm text-slate-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Provider Name</p>
                        <p class="mt-0.5 font-medium text-slate-900">
                            {{ $connection->connected_business_name ?? 'Kashtre Business' }}
                        </p>
                    </div>
                    @if($connection->connectedBusiness)
                        <div class="mt-3 sm:mt-0">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Code</p>
                            <p class="mt-0.5 font-mono text-sm text-slate-900 bg-slate-50 inline-flex px-2 py-1 rounded">
                                {{ $connection->connectedBusiness->code ?? 'N/A' }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Connection Type</p>
                        <p class="mt-0.5 font-medium text-slate-900">
                            {{ $connection->connection_type ? ucfirst(str_replace('_', ' ', $connection->connection_type)) : 'Standard' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                        <p class="mt-0.5">
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
                        </p>
                    </div>
                </div>

                @if($connection->notes)
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Internal Notes</p>
                        <p class="text-sm text-slate-700">
                            {{ $connection->notes }}
                        </p>
                    </div>
                @endif

                @if(!$connection->isActive())
                    <div class="border-t border-slate-200 pt-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Block Information</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Reason:</span>
                                <span class="font-medium text-slate-900">{{ $connection->block_reason }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Date:</span>
                                <span class="font-medium text-slate-900">{{ $connection->blocked_at?->format('M d, Y H:i') }}</span>
                            </div>
                            @if($connection->blockedByUser)
                                <div class="flex justify-between">
                                    <span class="text-slate-600">By:</span>
                                    <span class="font-medium text-slate-900">{{ $connection->blockedByUser->name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Coverage Snapshot</h2>
            </div>
            <div class="px-6 py-5 space-y-4 text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Available Categories</p>
                    @if(!empty($availableCategories))
                        <div class="flex flex-wrap gap-1">
                            @foreach($availableCategories as $cat)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
                                    {{ ucfirst($cat) }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-500">
                            All high-level categories are currently controlled by exclusions.
                        </p>
                    @endif
                </div>

                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Excluded Categories</p>
                    @if(!empty($excludedCategories))
                        <div class="flex flex-wrap gap-1">
                            @foreach($excludedCategories as $cat)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-100">
                                    {{ ucfirst($cat) }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-500">
                            No entire categories are excluded; see detailed rules below for procedure-level exclusions.
                        </p>
                    @endif
                </div>

                <p class="text-[11px] text-slate-500">
                    These rules work together with your global coverage decision matrix and plan-level settings.
                </p>
            </div>
        </div>
    </div>

    <!-- Financial card linking to independent page -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Financial</h2>
                <p class="text-xs text-slate-500 mt-1">
                    View totals of guarantees, client portions and excluded amounts for this provider.
                </p>
            </div>
            <a href="{{ route('connected-companies.financial', $connection->id) }}"
               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Open financial summary
            </a>
        </div>
    </div>

    <!-- Items available / excluded (two tabs) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <nav class="flex text-sm font-medium">
                    <button type="button" class="cc-items-tab px-4 py-2 border-b-2 border-blue-500 text-blue-600" data-target="available">
                        Items Available
                    </button>
                    <button type="button" class="cc-items-tab px-4 py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-700" data-target="excluded">
                        Items Excluded
                    </button>
                </nav>
                <form method="GET" action="{{ route('connected-companies.show', $connection->id) }}" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search items…"
                        class="w-56 px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Available items tab -->
        <div id="cc-items-available" class="px-6 py-4">
            @if($items->count() > 0)
                <p class="text-xs text-slate-500 mb-3">
                    Set what share of each item line <strong>this insurer</strong> pays at this provider. Default is <strong>100%</strong> (full line).
                    If you set e.g. <strong>50%</strong>, this insurer pays half and the remainder is sent to the <strong>next insurer in cascade priority</strong> on Kashtre (not an extra client charge when that insurer accepts it).
                    Use exclusions for items not covered at all.
                </p>
                <form method="POST" action="{{ route('connected-companies.item-coverages.update', $connection->id) }}">
                    @csrf
                    <input type="hidden" name="return_q" value="{{ request('q') }}">
                    <input type="hidden" name="return_page" value="{{ request('page') }}">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Item</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide w-40">Coverage %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($items as $index => $item)
                                @php
                                    $code = $item['code'] ?? '';
                                    $pct = (float) ($item['coverage_percent'] ?? 100);
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-slate-900">
                                        {{ $item['name'] ?? 'N/A' }}
                                        @if($code)
                                            <span class="text-xs text-slate-400 font-mono ml-1">({{ $code }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($code)
                                            <input type="hidden" name="coverages[{{ $index }}][service_code]" value="{{ $code }}">
                                            <div class="flex items-center gap-1">
                                                <input
                                                    type="number"
                                                    name="coverages[{{ $index }}][coverage_percent]"
                                                    value="{{ $pct == (int) $pct ? (int) $pct : number_format($pct, 2, '.', '') }}"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-24 border border-slate-300 rounded-md px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                                >
                                                <span class="text-slate-500 text-xs">%</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">No code</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 px-2">
                        <div>
                            {{ $items->appends(['q' => request('q')])->links() }}
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            Save coverage %
                        </button>
                    </div>
                </form>
            @else
                <div class="px-6 py-10 text-center text-sm text-slate-500">
                    <p>No items match your current filters.</p>
                    <p class="mt-2 text-xs text-slate-400">
                        Clear the search box or adjust Kashtre third-party exclusions to update availability.
                    </p>
                </div>
            @endif
        </div>

        <!-- Excluded items tab -->
        <div id="cc-items-excluded" class="px-6 py-4 hidden">
            <div class="space-y-6">
                <!-- Service Categories Exclusion Section -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Exclude Service Categories</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Exclude entire service categories from this provider. This will prevent any services in these categories from being authorized.
                    </p>

                    @if($excludedCategories->isNotEmpty())
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-xs font-medium text-red-800 mb-2">Excluded Categories:</p>
                            <ul class="text-xs text-red-700 space-y-1">
                                @foreach($excludedCategories as $category)
                                    <li class="flex items-center">
                                        <span class="text-red-500 mr-2">✕</span>
                                        {{ ucfirst($category) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('connected-companies.category-exclusions.store', $connection->id) }}" class="space-y-3 max-w-md">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-2" for="service_categories">
                                Select categories to exclude
                            </label>
                            <div class="space-y-2 border border-slate-300 rounded-md p-3 bg-slate-50">
                                @if($serviceCategories->isNotEmpty())
                                    @foreach($serviceCategories as $category)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="service_categories[]"
                                                value="{{ $category->slug }}"
                                                {{ $excludedCategories->contains($category->slug) ? 'checked disabled' : '' }}
                                                class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                                            >
                                            <span class="text-sm text-slate-700">
                                                {{ $category->name }}
                                            </span>
                                            @if($category->description)
                                                <span class="text-xs text-slate-500">— {{ $category->description }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                @else
                                    <p class="text-xs text-slate-500">No service categories available.</p>
                                @endif
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">
                                Select one or more categories. Already excluded categories are shown as disabled.
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="category_exclusion_reason">
                                Reason (optional)
                            </label>
                            <input
                                type="text"
                                name="reason"
                                id="category_exclusion_reason"
                                class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-red-500 focus:border-red-500"
                                placeholder="e.g. Not covered in our contract with this provider"
                            >
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                                Exclude categories
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Exclude Individual Services</h3>
                    @if($excludedItems->isNotEmpty())
                        <ul class="text-sm text-slate-800 space-y-1">
                            @foreach($excludedItems as $item)
                                <li class="flex items-start">
                                    <span class="mt-0.5 mr-1 text-red-500">•</span>
                                    <span>{{ $item['name'] ?? 'N/A' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-500">
                            No items are currently excluded from Kashtre third-party settings for this provider.
                        </p>
                    @endif
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <h3 class="text-xs font-semibold text-slate-700 mb-2">Additional exclusions by {{ $insuranceCompany->name }}</h3>
                    <p class="text-[11px] text-slate-500 mb-3">
                        These exclusions are specific to your insurer portal and do not change Kashtre's settings. They will still be enforced when you process coverage for this provider.
                    </p>

                    @if($localExclusions->isNotEmpty())
                        <ul class="text-xs text-slate-800 space-y-1 mb-3">
                            @foreach($localExclusions as $ex)
                                <li>
                                    <span class="text-slate-900">
                                        {{ $ex->item_name ?? 'Unknown item' }}
                                    </span>
                                    @if($ex->reason)
                                        <span class="text-slate-500 ml-1">— {{ $ex->reason }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-slate-500 mb-3">
                            No additional local exclusions defined yet.
                        </p>
                    @endif

                    <div class="mb-6 flex flex-col gap-4">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:flex-wrap gap-3">
                            <form method="POST" action="{{ route('connected-companies.local-exclusions.exclude-all', $connection->id) }}" class="flex flex-1 min-w-0 flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3" onsubmit="return confirm('Exclude ALL currently available items for this provider? This adds a local exclusion for each item with a service code.');">
                                @csrf
                                <div class="flex-1 min-w-[200px] max-w-md">
                                    <label class="block text-xs font-medium text-slate-700 mb-1" for="exclude_all_reason">Reason (optional, applies to all)</label>
                                    <input
                                        type="text"
                                        name="reason"
                                        id="exclude_all_reason"
                                        class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-red-500 focus:border-red-500"
                                        placeholder="e.g. Contract excludes all procedures at this site"
                                    >
                                    <p class="mt-1 text-[11px] text-slate-500">Uses the full provider item list (not the search filter on the other tab).</p>
                                </div>
                                <button
                                    type="submit"
                                    class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-md border border-red-300 bg-red-50 text-red-800 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if(($excludeAllEligibleCount ?? 0) === 0) disabled title="No available items with codes" @endif
                                >
                                    Exclude all ({{ $excludeAllEligibleCount ?? 0 }})
                                </button>
                            </form>
                            <form method="POST" action="{{ route('connected-companies.local-exclusions.unexclude-all', $connection->id) }}" class="shrink-0 flex items-end" onsubmit="return confirm('Remove all item-level local exclusions for this provider? Category exclusions are not changed.');">
                                @csrf
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if(($localItemExclusionCount ?? 0) === 0) disabled title="No local item exclusions" @endif
                                >
                                    Unexclude all ({{ $localItemExclusionCount ?? 0 }})
                                </button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('connected-companies.local-exclusions.store', $connection->id) }}" class="space-y-3 max-w-md">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="service_codes">
                                Services to exclude (from available list)
                            </label>
                            <select
                                name="service_codes[]"
                                id="service_codes"
                                multiple
                                size="8"
                                class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-blue-500 focus:border-blue-500"
                                @if($items->count() > 0) required @endif
                            >
                                @foreach($items as $item)
                                    @if(!empty($item['code']))
                                        <option value="{{ $item['code'] }}">
                                            {{ $item['name'] ?? 'N/A' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">
                                Hold Ctrl (Windows) or Command (Mac) to select multiple services. This list is based on items currently available for this provider.
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="local_exclusion_reason">
                                Reason (optional)
                            </label>
                            <input
                                type="text"
                                name="reason"
                                id="local_exclusion_reason"
                                class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g. Not covered in our contract with this provider"
                            >
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                                Add local exclusion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Management (last on page: suspend / block / reactivate) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Connection Management</h2>
        </div>
        <div class="px-6 py-5">
            @if($connection->isActive())
                <div class="space-y-4">
                    <p class="text-sm text-slate-600 mb-4">This connection is currently active. You can suspend or block it.</p>

                    <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4 rounded">
                        <h3 class="font-semibold text-yellow-900 mb-3">⊘ Suspend Connection</h3>
                        <p class="text-sm text-yellow-800 mb-3">Temporarily suspend this provider. They can be reactivated later.</p>
                        <form action="{{ route('connected-companies.block', $connection->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="status" value="suspended">
                            <div>
                                <label class="block text-sm font-medium text-yellow-900 mb-2">Reason for suspension:</label>
                                <textarea name="reason" required placeholder="Enter reason for suspension..."
                                          class="w-full px-3 py-2 border border-yellow-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" rows="2"></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-yellow-600 text-white hover:bg-yellow-700"
                                    onclick="return confirm('Suspend this provider connection? They cannot authorize visits or sync until reactivated.')">
                                Suspend
                            </button>
                        </form>
                    </div>

                    <div class="border-l-4 border-red-400 bg-red-50 p-4 rounded">
                        <h3 class="font-semibold text-red-900 mb-3">✕ Block Connection</h3>
                        <p class="text-sm text-red-800 mb-3">Permanently block this provider. This prevents any new authorizations.</p>
                        <form action="{{ route('connected-companies.block', $connection->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="status" value="blocked">
                            <div>
                                <label class="block text-sm font-medium text-red-900 mb-2">Reason for blocking:</label>
                                <textarea name="reason" required placeholder="Enter reason for blocking..."
                                          class="w-full px-3 py-2 border border-red-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" rows="2"></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700"
                                    onclick="return confirm('Are you sure you want to block this connection?')">
                                Block
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="border-l-4 border-green-400 bg-green-50 p-4 rounded">
                    <h3 class="font-semibold text-green-900 mb-3">✓ Reactivate Connection</h3>
                    <p class="text-sm text-green-800 mb-4">
                        This connection is currently {{ $connection->isSuspended() ? 'suspended' : 'blocked' }}.
                        Click below to reactivate it.
                    </p>
                    <form action="{{ route('connected-companies.reactivate', $connection->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700"
                                onclick="return confirm('Are you sure you want to reactivate this connection?')">
                            Reactivate Connection
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = document.querySelectorAll('.cc-items-tab');
        var available = document.getElementById('cc-items-available');
        var excluded = document.getElementById('cc-items-excluded');

        if (!tabs.length || !available || !excluded) {
            return;
        }

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = this.getAttribute('data-target'); // 'available' or 'excluded'

                tabs.forEach(function (b) {
                    b.classList.remove('border-blue-500', 'text-blue-600');
                    b.classList.add('border-transparent', 'text-slate-500');
                });

                this.classList.add('border-blue-500', 'text-blue-600');
                this.classList.remove('border-transparent', 'text-slate-500');

                if (target === 'available') {
                    available.classList.remove('hidden');
                    excluded.classList.add('hidden');
                } else {
                    available.classList.add('hidden');
                    excluded.classList.remove('hidden');
                }
            });
        });
    });
</script>

@endsection
