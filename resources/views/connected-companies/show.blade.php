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
                    <strong>Plan service-category coverage</strong> (on Plans) overrides these per-item % when the visit category is set below 100%.
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

                <div class="border-t border-slate-200 pt-4" id="cc-local-exclusions-section">
                    <h3 class="text-xs font-semibold text-slate-700 mb-2">Additional exclusions by {{ $insuranceCompany->name }}</h3>
                    <p class="text-[11px] text-slate-500 mb-3">
                        These exclusions are specific to your insurer portal and do not change Kashtre's settings. They will still be enforced when you process coverage for this provider.
                    </p>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            id="cc-btn-show-exclusion-picker"
                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(($excludeAllEligibleCount ?? 0) === 0) disabled @endif
                        >
                            Select items to exclude
                        </button>
                        <button
                            type="button"
                            id="cc-btn-exclude-all-picker"
                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-red-300 bg-red-50 text-red-800 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(($excludeAllEligibleCount ?? 0) === 0) disabled @endif
                        >
                            Exclude all ({{ $excludeAllEligibleCount ?? 0 }}) — review &amp; confirm
                        </button>
                        <form method="POST" action="{{ route('connected-companies.local-exclusions.unexclude-all', $connection->id) }}" class="inline" onsubmit="return confirm('Remove all item-level local exclusions for this provider? Category exclusions are not changed.');">
                            @csrf
                            <button
                                type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @if(($localItemExclusionCount ?? 0) === 0) disabled @endif
                            >
                                Unexclude all ({{ $localItemExclusionCount ?? 0 }})
                            </button>
                        </form>
                    </div>

                    {{-- Add exclusions: checkbox picker --}}
                    <div id="cc-exclusion-picker" class="hidden mb-6 border border-red-200 rounded-lg bg-red-50/40 p-4">
                        <form method="POST" action="{{ route('connected-companies.local-exclusions.store', $connection->id) }}" id="cc-exclusion-picker-form">
                            @csrf
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Choose items to exclude</h4>
                                    <p class="text-[11px] text-slate-600 mt-0.5">
                                        <span id="cc-exclusion-selected-count">0</span> of {{ $exclusionPickerItems->count() }} selected
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="cc-exclusion-select-all" class="px-2 py-1 text-xs font-medium rounded border border-slate-300 bg-white hover:bg-slate-50">Select all</button>
                                    <button type="button" id="cc-exclusion-deselect-all" class="px-2 py-1 text-xs font-medium rounded border border-slate-300 bg-white hover:bg-slate-50">Deselect all</button>
                                    <button type="button" id="cc-exclusion-picker-hide" class="px-2 py-1 text-xs font-medium rounded border border-slate-300 bg-white hover:bg-slate-50">Cancel</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <input
                                    type="search"
                                    id="cc-exclusion-search"
                                    placeholder="Search by name or code…"
                                    class="w-full max-w-md border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-red-500 focus:border-red-500"
                                    autocomplete="off"
                                >
                            </div>
                            <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-md bg-white p-2 space-y-0.5" id="cc-exclusion-checkbox-list">
                                @forelse($exclusionPickerItems as $item)
                                    @php $code = $item['code'] ?? ''; @endphp
                                    <label
                                        class="cc-exclusion-row flex items-start gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer"
                                        data-search="{{ mb_strtolower(($item['name'] ?? '') . ' ' . $code) }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="service_codes[]"
                                            value="{{ $code }}"
                                            class="cc-exclusion-cb mt-0.5 h-4 w-4 text-red-600 border-slate-300 rounded focus:ring-red-500"
                                        >
                                        <span class="text-sm text-slate-800">
                                            {{ $item['name'] ?? 'N/A' }}
                                            <span class="text-xs text-slate-400 font-mono ml-1">({{ $code }})</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500 px-2 py-4 text-center">No items available to exclude (all may already be excluded).</p>
                                @endforelse
                            </div>
                            <div class="mt-3 max-w-md">
                                <label class="block text-xs font-medium text-slate-700 mb-1" for="local_exclusion_reason">Reason (optional, applies to selected)</label>
                                <input
                                    type="text"
                                    name="reason"
                                    id="local_exclusion_reason"
                                    class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:ring-red-500 focus:border-red-500"
                                    placeholder="e.g. Not covered in our contract with this provider"
                                >
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700 disabled:opacity-50"
                                    id="cc-exclusion-submit"
                                >
                                    Exclude selected items
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Current local exclusions: checkbox manager --}}
                    @php
                        $localItemExclusions = $localExclusions->filter(fn ($ex) => ! empty($ex->service_code));
                    @endphp
                    @if($localItemExclusions->isNotEmpty())
                        <div class="border border-slate-200 rounded-lg p-4 bg-white mb-4">
                            <h4 class="text-sm font-semibold text-slate-900 mb-2">Currently excluded locally ({{ $localItemExclusions->count() }})</h4>
                            <p class="text-[11px] text-slate-500 mb-3">Check items you want to <strong>remove</strong> from exclusions (unexclude), then confirm.</p>
                            <form method="POST" action="{{ route('connected-companies.local-exclusions.destroy', $connection->id) }}" id="cc-unexclusion-form">
                                @csrf
                                <div class="mb-2 flex flex-wrap gap-2">
                                    <button type="button" id="cc-unexclusion-select-all" class="px-2 py-1 text-xs font-medium rounded border border-slate-300 bg-white hover:bg-slate-50">Select all</button>
                                    <button type="button" id="cc-unexclusion-deselect-all" class="px-2 py-1 text-xs font-medium rounded border border-slate-300 bg-white hover:bg-slate-50">Deselect all</button>
                                </div>
                                <div class="max-h-48 overflow-y-auto border border-slate-100 rounded-md p-2 space-y-0.5 mb-3">
                                    @foreach($localItemExclusions as $ex)
                                        <label class="flex items-start gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="service_codes[]"
                                                value="{{ $ex->service_code }}"
                                                class="cc-unexclusion-cb mt-0.5 h-4 w-4 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                            >
                                            <span class="text-sm text-slate-800">
                                                {{ $ex->item_name ?? $ex->service_code }}
                                                <span class="text-xs text-slate-400 font-mono ml-1">({{ $ex->service_code }})</span>
                                                @if($ex->reason)
                                                    <span class="text-xs text-slate-500 block">{{ $ex->reason }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-green-300 bg-green-50 text-green-800 hover:bg-green-100"
                                    onclick="return confirm('Remove local exclusions for the selected items?');"
                                >
                                    Unexclude selected items
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="text-xs text-slate-500 mb-4">
                            No additional local item exclusions defined yet.
                        </p>
                    @endif
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

        function activateTab(target) {
            if (!tabs.length || !available || !excluded) {
                return;
            }
            tabs.forEach(function (b) {
                var isActive = b.getAttribute('data-target') === target;
                b.classList.toggle('border-blue-500', isActive);
                b.classList.toggle('text-blue-600', isActive);
                b.classList.toggle('border-transparent', !isActive);
                b.classList.toggle('text-slate-500', !isActive);
            });
            if (target === 'available') {
                available.classList.remove('hidden');
                excluded.classList.add('hidden');
            } else {
                available.classList.add('hidden');
                excluded.classList.remove('hidden');
            }
        }

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(this.getAttribute('data-target'));
            });
        });

        if (window.location.hash === '#cc-items-excluded') {
            activateTab('excluded');
        }

        var picker = document.getElementById('cc-exclusion-picker');
        var pickerForm = document.getElementById('cc-exclusion-picker-form');
        if (!picker || !pickerForm) {
            return;
        }

        var checkboxes = picker.querySelectorAll('.cc-exclusion-cb');
        var countEl = document.getElementById('cc-exclusion-selected-count');
        var searchInput = document.getElementById('cc-exclusion-search');
        var submitBtn = document.getElementById('cc-exclusion-submit');

        function updateExclusionCount() {
            var visible = 0;
            var selected = 0;
            picker.querySelectorAll('.cc-exclusion-row').forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                visible++;
                var cb = row.querySelector('.cc-exclusion-cb');
                if (cb && cb.checked) {
                    selected++;
                }
            });
            if (countEl) {
                countEl.textContent = String(selected);
            }
            if (submitBtn) {
                submitBtn.disabled = selected === 0;
            }
        }

        function setAllExclusionChecked(checked) {
            picker.querySelectorAll('.cc-exclusion-row').forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                var cb = row.querySelector('.cc-exclusion-cb');
                if (cb) {
                    cb.checked = checked;
                }
            });
            updateExclusionCount();
        }

        function showPicker(selectAll) {
            picker.classList.remove('hidden');
            if (selectAll) {
                setAllExclusionChecked(true);
            } else {
                updateExclusionCount();
            }
            picker.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hidePicker() {
            picker.classList.add('hidden');
        }

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', updateExclusionCount);
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                picker.querySelectorAll('.cc-exclusion-row').forEach(function (row) {
                    var hay = row.getAttribute('data-search') || '';
                    row.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
                });
                updateExclusionCount();
            });
        }

        var btnShow = document.getElementById('cc-btn-show-exclusion-picker');
        var btnExcludeAll = document.getElementById('cc-btn-exclude-all-picker');
        var btnSelectAll = document.getElementById('cc-exclusion-select-all');
        var btnDeselectAll = document.getElementById('cc-exclusion-deselect-all');
        var btnHide = document.getElementById('cc-exclusion-picker-hide');

        if (btnShow) {
            btnShow.addEventListener('click', function () {
                activateTab('excluded');
                showPicker(false);
            });
        }
        if (btnExcludeAll) {
            btnExcludeAll.addEventListener('click', function () {
                activateTab('excluded');
                showPicker(true);
            });
        }
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function () { setAllExclusionChecked(true); });
        }
        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', function () { setAllExclusionChecked(false); });
        }
        if (btnHide) {
            btnHide.addEventListener('click', hidePicker);
        }

        if (pickerForm) {
            pickerForm.addEventListener('submit', function (e) {
                var selected = picker.querySelectorAll('.cc-exclusion-cb:checked').length;
                if (selected === 0) {
                    e.preventDefault();
                    alert('Select at least one item to exclude.');
                    return;
                }
                if (!confirm('Exclude ' + selected + ' selected item(s) for this provider?')) {
                    e.preventDefault();
                }
            });
        }

        updateExclusionCount();

        var unexSelectAll = document.getElementById('cc-unexclusion-select-all');
        var unexDeselectAll = document.getElementById('cc-unexclusion-deselect-all');
        if (unexSelectAll) {
            unexSelectAll.addEventListener('click', function () {
                document.querySelectorAll('.cc-unexclusion-cb').forEach(function (cb) { cb.checked = true; });
            });
        }
        if (unexDeselectAll) {
            unexDeselectAll.addEventListener('click', function () {
                document.querySelectorAll('.cc-unexclusion-cb').forEach(function (cb) { cb.checked = false; });
            });
        }

        var unexForm = document.getElementById('cc-unexclusion-form');
        if (unexForm) {
            unexForm.addEventListener('submit', function (e) {
                var n = document.querySelectorAll('.cc-unexclusion-cb:checked').length;
                if (n === 0) {
                    e.preventDefault();
                    alert('Select at least one item to unexclude.');
                }
            });
        }
    });
</script>

@endsection
