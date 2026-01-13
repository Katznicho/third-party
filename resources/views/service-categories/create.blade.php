@extends('layouts.dashboard')

@section('title', 'Create Product')
@section('page-title', 'Create Product')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Create New Product</h1>
            <p class="text-slate-600 mt-1">Add a new service category/product</p>
        </div>
        <a href="{{ route('service-categories.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Products
        </a>
    </div>

    <!-- Create Form -->
    <form action="{{ route('service-categories.store') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
        @csrf

        <!-- Basic Information Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Inpatient, Outpatient, Maternity">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-2">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" placeholder="e.g., INP, OUT, MAT" style="text-transform: uppercase;">
                    <p class="mt-1 text-xs text-slate-500">Unique code (e.g., INP, OUT, MAT, OPT, DEN, FUN)</p>
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
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Brief description of the service category">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Configuration Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Configuration</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Is Mandatory -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_mandatory" id="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="is_mandatory" class="ml-2 block text-sm text-slate-700">Mandatory Benefit</label>
                    <p class="ml-2 text-xs text-slate-500">(Inpatient is typically mandatory)</p>
                </div>

                <!-- Requires Maternity Wait -->
                <div class="flex items-center">
                    <input type="checkbox" name="requires_maternity_wait" id="requires_maternity_wait" value="1" {{ old('requires_maternity_wait') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="requires_maternity_wait" class="ml-2 block text-sm text-slate-700">Requires Maternity Wait Period</label>
                    <p class="ml-2 text-xs text-slate-500">(Prior year payment required)</p>
                </div>

                <!-- Requires Optical Dental Pair -->
                <div class="flex items-center">
                    <input type="checkbox" name="requires_optical_dental_pair" id="requires_optical_dental_pair" value="1" {{ old('requires_optical_dental_pair') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="requires_optical_dental_pair" class="ml-2 block text-sm text-slate-700">Requires Optical & Dental Pair</label>
                    <p class="ml-2 text-xs text-slate-500">(Must be selected together)</p>
                </div>

                <!-- Waiting Period Days -->
                <div>
                    <label for="waiting_period_days" class="block text-sm font-medium text-slate-700 mb-2">Waiting Period (Days)</label>
                    <input type="number" name="waiting_period_days" id="waiting_period_days" value="{{ old('waiting_period_days', 0) }}" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-500">Days before benefit can be used</p>
                    @error('waiting_period_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
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
            <a href="{{ route('service-categories.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Create Product
            </button>
        </div>
    </form>
</div>

<script>
    // Convert code to uppercase on input
    document.getElementById('code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
</script>
@endsection
