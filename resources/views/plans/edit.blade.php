@extends('layouts.dashboard')

@section('title', 'Edit Plan')
@section('page-title', 'Edit Plan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Plan</h1>
            <p class="text-slate-600 mt-1">Update plan information and associated products</p>
        </div>
        <a href="{{ route('plans.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Plans
        </a>
    </div>

    <!-- Edit Form -->
    <form action="{{ route('plans.update', $plan) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $plan->name) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Prestige, Executive, Standard Plus">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-2">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code', $plan->code) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" placeholder="e.g., PRE, EXE, STD+" style="text-transform: uppercase;">
                    <p class="mt-1 text-xs text-slate-500">Unique code (e.g., PRE, EXE, STD+, STD, REG, BUD)</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">Display order (lower numbers appear first)</p>
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Brief description of the plan">{{ old('description', $plan->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Products (Service Categories) Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Associated Products</h2>
            <p class="text-sm text-slate-600 mb-4">Select products (service categories) and configure benefit amounts for this plan</p>
            <p class="text-xs text-blue-600 mb-4 font-medium">💡 Standard Categories: Inpatient, Outpatient, Maternity, Optical, Dental, Funeral Expenses</p>
            
            <div class="space-y-4" id="products-container">
                @php
                    $planCategories = $plan->serviceCategories->keyBy('id');
                    $standardCategories = ['Inpatient', 'Outpatient', 'Maternity', 'Optical', 'Dental', 'Funeral Expenses'];
                @endphp
                @foreach($serviceCategories as $category)
                    @php
                        $pivot = $planCategories->get($category->id);
                        $isChecked = old('service_categories.' . $category->id . '.id', $pivot !== null);
                        $isStandard = in_array($category->name, $standardCategories);
                    @endphp
                    <div class="border border-slate-200 rounded-lg p-4 product-item {{ $isStandard ? 'bg-blue-50 border-blue-300' : '' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="service_categories[{{ $category->id }}][id]" value="{{ $category->id }}" id="category_{{ $category->id }}" class="category-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded" {{ $isChecked ? 'checked' : '' }} onchange="toggleCategoryFields({{ $category->id }})">
                                <label for="category_{{ $category->id }}" class="ml-3 block text-sm font-medium text-slate-900">
                                    {{ $category->name }} <span class="text-xs text-slate-500">({{ $category->code }})</span>
                                    @if($isStandard)
                                        <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Standard</span>
                                    @endif
                                    @if($category->is_mandatory)
                                        <span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">Mandatory</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                        
                        <div id="fields_{{ $category->id }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3" style="display: {{ $isChecked ? 'grid' : 'none' }};">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Benefit Amount (UGX) <span class="text-red-500">*</span></label>
                                <input type="number" name="service_categories[{{ $category->id }}][benefit_amount]" step="0.01" min="0" value="{{ old('service_categories.' . $category->id . '.benefit_amount', $pivot->pivot->benefit_amount ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter benefit amount in UGX (e.g., 200000000)">
                                <p class="mt-1 text-xs text-slate-500">Required for Inpatient, Outpatient, Maternity, Optical, Dental, Funeral Expenses</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Co-pay Percentage (%)</label>
                                <input type="number" name="service_categories[{{ $category->id }}][copay_percentage]" step="0.01" min="0" max="100" value="{{ old('service_categories.' . $category->id . '.copay_percentage', $pivot->pivot->copay_percentage ?? 0) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter co-pay percentage (0-100)">
                                <p class="mt-1 text-xs text-slate-500">Percentage the client pays (default: 0)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Deductible Amount (UGX)</label>
                                <input type="number" name="service_categories[{{ $category->id }}][deductible_amount]" step="0.01" min="0" value="{{ old('service_categories.' . $category->id . '.deductible_amount', $pivot->pivot->deductible_amount ?? 0) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter deductible amount (e.g., 100000)">
                                <p class="mt-1 text-xs text-slate-500">Amount client pays before insurance covers (default: 0)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Waiting Period (Days)</label>
                                <input type="number" name="service_categories[{{ $category->id }}][waiting_period_days]" min="0" value="{{ old('service_categories.' . $category->id . '.waiting_period_days', $pivot->pivot->waiting_period_days ?? ($category->waiting_period_days ?? 0)) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter waiting period in days">
                                <p class="mt-1 text-xs text-slate-500">Days before benefit becomes active (Maternity: 365 days)</p>
                            </div>
                            
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" name="service_categories[{{ $category->id }}][is_enabled]" value="1" {{ old('service_categories.' . $category->id . '.is_enabled', $pivot->pivot->is_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                                <label class="ml-2 block text-sm text-slate-700">Enabled for this plan</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Status Section -->
        <div>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-slate-700">Active</label>
                <p class="ml-2 text-xs text-slate-500">(Visible and available for selection)</p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-4 pt-4 border-t border-slate-200">
            <a href="{{ route('plans.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Update Plan
            </button>
        </div>
    </form>
</div>

<script>
    function toggleCategoryFields(categoryId) {
        const checkbox = document.getElementById('category_' + categoryId);
        const fields = document.getElementById('fields_' + categoryId);
        
        if (checkbox.checked) {
            fields.style.display = 'grid';
        } else {
            fields.style.display = 'none';
        }
    }

    // Convert code to uppercase on input
    document.getElementById('code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
</script>
@endsection
