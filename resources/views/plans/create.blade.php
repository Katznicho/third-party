@extends('layouts.dashboard')

@section('title', 'Create Plan')
@section('page-title', 'Create Plan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Create New Plan</h1>
            <p class="text-slate-600 mt-1">Add a new insurance plan with associated products</p>
        </div>
        <a href="{{ route('plans.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Plans
        </a>
    </div>

    <!-- Create Form -->
    <form action="{{ route('plans.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
        @csrf

        <!-- Basic Information Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Prestige, Executive, Standard Plus">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-2">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" placeholder="e.g., PRE, EXE, STD+" style="text-transform: uppercase;">
                    <p class="mt-1 text-xs text-slate-500">Unique code (e.g., PRE, EXE, STD+, STD, REG, BUD)</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">Display order (lower numbers appear first)</p>
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Brief description of the plan">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Age Limits & Effective Dates Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Enrollment & Availability</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Minimum Enrollment Age -->
                <div>
                    <label for="min_enrollment_age" class="block text-sm font-medium text-slate-700 mb-2">Minimum Enrollment Age</label>
                    <input type="number" name="min_enrollment_age" id="min_enrollment_age" value="{{ old('min_enrollment_age') }}" min="0" max="120" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 18">
                    <p class="mt-1 text-xs text-slate-500">Minimum age required to enroll (leave blank for no limit)</p>
                    @error('min_enrollment_age')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Maximum Enrollment Age -->
                <div>
                    <label for="max_enrollment_age" class="block text-sm font-medium text-slate-700 mb-2">Maximum Enrollment Age</label>
                    <input type="number" name="max_enrollment_age" id="max_enrollment_age" value="{{ old('max_enrollment_age') }}" min="0" max="120" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 65">
                    <p class="mt-1 text-xs text-slate-500">Maximum age allowed to enroll (leave blank for no limit)</p>
                    @error('max_enrollment_age')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Effective Start Date -->
                <div>
                    <label for="effective_start_date" class="block text-sm font-medium text-slate-700 mb-2">Plan Start Date</label>
                    <input type="date" name="effective_start_date" id="effective_start_date" value="{{ old('effective_start_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">When enrollment opens (leave blank for immediate availability)</p>
                    @error('effective_start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Effective End Date -->
                <div>
                    <label for="effective_end_date" class="block text-sm font-medium text-slate-700 mb-2">Plan End Date</label>
                    <input type="date" name="effective_end_date" id="effective_end_date" value="{{ old('effective_end_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">When enrollment closes (leave blank for no end date)</p>
                    @error('effective_end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Coverage Settings Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Coverage Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Dependent Coverage Multiplier -->
                <div>
                    <label for="dependent_coverage_multiplier" class="block text-sm font-medium text-slate-700 mb-2">Dependent Coverage Multiplier</label>
                    <input type="number" name="dependent_coverage_multiplier" id="dependent_coverage_multiplier" value="{{ old('dependent_coverage_multiplier', 0.50) }}" step="0.01" min="0" max="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.50">
                    <p class="mt-1 text-xs text-slate-500">Multiplier for dependent premiums (e.g., 0.50 = 50% of principal premium, default: 0.50)</p>
                    @error('dependent_coverage_multiplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Annual Max Coverage -->
                <div>
                    <label for="annual_max_coverage" class="block text-sm font-medium text-slate-700 mb-2">Annual Maximum Coverage (UGX)</label>
                    <input type="number" name="annual_max_coverage" id="annual_max_coverage" value="{{ old('annual_max_coverage') }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 500000000">
                    <p class="mt-1 text-xs text-slate-500">Maximum coverage amount per year (leave blank for unlimited)</p>
                    @error('annual_max_coverage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Lifetime Max Coverage -->
                <div>
                    <label for="lifetime_max_coverage" class="block text-sm font-medium text-slate-700 mb-2">Lifetime Maximum Coverage (UGX)</label>
                    <input type="number" name="lifetime_max_coverage" id="lifetime_max_coverage" value="{{ old('lifetime_max_coverage') }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 2000000000">
                    <p class="mt-1 text-xs text-slate-500">Maximum coverage amount over lifetime (leave blank for unlimited)</p>
                    @error('lifetime_max_coverage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Per Incident Max Coverage -->
                <div>
                    <label for="per_incident_max_coverage" class="block text-sm font-medium text-slate-700 mb-2">Per Incident Maximum Coverage (UGX)</label>
                    <input type="number" name="per_incident_max_coverage" id="per_incident_max_coverage" value="{{ old('per_incident_max_coverage') }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 100000000">
                    <p class="mt-1 text-xs text-slate-500">Maximum coverage per incident/claim (leave blank for unlimited)</p>
                    @error('per_incident_max_coverage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Premium Calculation Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Premium Calculation Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Premium Calculation Method -->
                <div>
                    <label for="premium_calculation_method" class="block text-sm font-medium text-slate-700 mb-2">Premium Calculation Method</label>
                    <select name="premium_calculation_method" id="premium_calculation_method" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="benefit_based" {{ old('premium_calculation_method', 'benefit_based') === 'benefit_based' ? 'selected' : '' }}>Benefit Based (Sum of selected benefits)</option>
                        <option value="fixed" {{ old('premium_calculation_method') === 'fixed' ? 'selected' : '' }}>Fixed (Use base premium)</option>
                        <option value="hybrid" {{ old('premium_calculation_method') === 'hybrid' ? 'selected' : '' }}>Hybrid (Base + Benefits)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">How premium is calculated for this plan</p>
                    @error('premium_calculation_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Base Premium -->
                <div>
                    <label for="base_premium" class="block text-sm font-medium text-slate-700 mb-2">Base Premium (UGX)</label>
                    <input type="number" name="base_premium" id="base_premium" value="{{ old('base_premium') }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 1000000">
                    <p class="mt-1 text-xs text-slate-500">Base premium amount (used for fixed or hybrid calculation methods)</p>
                    @error('base_premium')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Insurance Training Levy Percentage -->
                <div>
                    <label for="insurance_training_levy_percentage" class="block text-sm font-medium text-slate-700 mb-2">Insurance Training Levy (%)</label>
                    <input type="number" name="insurance_training_levy_percentage" id="insurance_training_levy_percentage" value="{{ old('insurance_training_levy_percentage', 0.50) }}" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.50">
                    <p class="mt-1 text-xs text-slate-500">Percentage of premium for training levy (default: 0.50%)</p>
                    @error('insurance_training_levy_percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stamp Duty Amount -->
                <div>
                    <label for="stamp_duty_amount" class="block text-sm font-medium text-slate-700 mb-2">Stamp Duty Amount (UGX)</label>
                    <input type="number" name="stamp_duty_amount" id="stamp_duty_amount" value="{{ old('stamp_duty_amount', 35000) }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="35000">
                    <p class="mt-1 text-xs text-slate-500">Fixed stamp duty amount (default: 35,000 UGX)</p>
                    @error('stamp_duty_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Plan Image & Terms Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Plan Image & Terms</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Plan Image -->
                <div>
                    <label for="image" class="block text-sm font-medium text-slate-700 mb-2">Plan Image/Icon</label>
                    <input type="file" name="image" id="image" accept="image/*" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">Upload an image/icon for this plan (max 2MB, jpeg/png/jpg/gif/svg)</p>
                    @error('image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Terms Link -->
                <div>
                    <label for="terms_link" class="block text-sm font-medium text-slate-700 mb-2">Terms & Conditions Link</label>
                    <input type="url" name="terms_link" id="terms_link" value="{{ old('terms_link') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com/terms">
                    <p class="mt-1 text-xs text-slate-500">URL to terms and conditions document</p>
                    @error('terms_link')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Terms & Conditions Text -->
                <div class="md:col-span-2">
                    <label for="terms_and_conditions" class="block text-sm font-medium text-slate-700 mb-2">Terms & Conditions (Text)</label>
                    <textarea name="terms_and_conditions" id="terms_and_conditions" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter terms and conditions text here...">{{ old('terms_and_conditions') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Enter terms and conditions text (or use link above)</p>
                    @error('terms_and_conditions')
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
                    $standardCategories = ['Inpatient', 'Outpatient', 'Maternity', 'Optical', 'Dental', 'Funeral Expenses'];
                @endphp
                @foreach($serviceCategories as $category)
                    @php
                        $isStandard = in_array($category->name, $standardCategories);
                    @endphp
                    <div class="border border-slate-200 rounded-lg p-4 product-item {{ $isStandard ? 'bg-blue-50 border-blue-300' : '' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="service_categories[{{ $category->id }}][id]" value="{{ $category->id }}" id="category_{{ $category->id }}" class="category-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded" onchange="toggleCategoryFields({{ $category->id }})">
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
                        
                        <div id="fields_{{ $category->id }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3" style="display: none;">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Benefit Amount (UGX) <span class="text-red-500">*</span></label>
                                <input type="number" name="service_categories[{{ $category->id }}][benefit_amount]" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter benefit amount in UGX (e.g., 200000000)">
                                <p class="mt-1 text-xs text-slate-500">Amount provided/covered by insurance</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Base Amount (UGX) <span class="text-red-500">*</span></label>
                                <input type="number" name="service_categories[{{ $category->id }}][base_amount]" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter base amount in UGX">
                                <p class="mt-1 text-xs text-slate-500">Amount the client pays</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Waiting Period (Days)</label>
                                <input type="number" name="service_categories[{{ $category->id }}][waiting_period_days]" min="0" value="{{ $category->waiting_period_days ?? 0 }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter waiting period in days">
                                <p class="mt-1 text-xs text-slate-500">Days before benefit becomes active (Maternity: 365 days)</p>
                            </div>
                            
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" name="service_categories[{{ $category->id }}][is_enabled]" value="1" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
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
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
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
                Create Plan
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
