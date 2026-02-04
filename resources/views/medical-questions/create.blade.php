@extends('layouts.dashboard')

@section('title', 'Create Medical Question')
@section('page-title', 'Create Medical Question')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('medical-questions.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Question Text -->
            <div>
                <label for="question_text" class="block text-sm font-medium text-slate-700 mb-2">
                    Question Text <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="question_text" 
                    id="question_text" 
                    rows="4"
                    required
                    placeholder="Enter the medical question"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('question_text') border-red-300 @enderror"
                >{{ old('question_text') }}</textarea>
                @error('question_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Question Type -->
            <div>
                <label for="question_type" class="block text-sm font-medium text-slate-700 mb-2">
                    Question Type <span class="text-red-500">*</span>
                </label>
                <select 
                    name="question_type" 
                    id="question_type"
                    required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('question_type') border-red-300 @enderror"
                >
                    <option value="yes_no" {{ old('question_type') == 'yes_no' ? 'selected' : '' }}>Yes/No</option>
                    <option value="text" {{ old('question_type') == 'text' ? 'selected' : '' }}>Text</option>
                    <option value="date" {{ old('question_type') == 'date' ? 'selected' : '' }}>Date</option>
                    <option value="number" {{ old('question_type') == 'number' ? 'selected' : '' }}>Number</option>
                </select>
                @error('question_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Exclusion List -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <div class="flex items-center mb-4">
                    <input 
                        type="checkbox" 
                        name="has_exclusion_list" 
                        id="has_exclusion_list" 
                        value="1"
                        {{ old('has_exclusion_list') ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                    >
                    <label for="has_exclusion_list" class="ml-2 block text-sm font-medium text-slate-700">
                        Include in Exclusion List
                    </label>
                </div>
                <p class="text-xs text-slate-500 mb-3">When enabled, a "YES" response or matching keywords will trigger exclusion list criteria, affecting policy eligibility and claims processing.</p>
                
                <div id="exclusion-keywords-field" style="display: {{ old('has_exclusion_list') ? 'block' : 'none' }};">
                    <label for="exclusion_keywords" class="block text-sm font-medium text-slate-700 mb-2">
                        Exclusion Keywords (comma-separated)
                    </label>
                    <input 
                        type="text" 
                        name="exclusion_keywords" 
                        id="exclusion_keywords"
                        value="{{ old('exclusion_keywords') }}"
                        placeholder="e.g., HIV, AIDS, cancer, diabetes"
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('exclusion_keywords') border-red-300 @enderror"
                    >
                    <p class="mt-1 text-xs text-slate-500">Enter keywords separated by commas. If a response contains any of these keywords, it will trigger exclusion.</p>
                    @error('exclusion_keywords')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Additional Info -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <div class="flex items-center mb-4">
                    <input 
                        type="checkbox" 
                        name="requires_additional_info" 
                        id="requires_additional_info" 
                        value="1"
                        {{ old('requires_additional_info') ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                    >
                    <label for="requires_additional_info" class="ml-2 block text-sm font-medium text-slate-700">
                        Requires Additional Information
                    </label>
                </div>
                
                <div id="additional-info-fields" style="display: {{ old('requires_additional_info') ? 'block' : 'none' }};">
                    <div class="mb-4">
                        <label for="additional_info_type" class="block text-sm font-medium text-slate-700 mb-2">
                            Additional Info Type
                        </label>
                        <select 
                            name="additional_info_type" 
                            id="additional_info_type"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                            <option value="text" {{ old('additional_info_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="date" {{ old('additional_info_type') == 'date' ? 'selected' : '' }}>Date</option>
                            <option value="table" {{ old('additional_info_type') == 'table' ? 'selected' : '' }}>Table (Medications)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="additional_info_label" class="block text-sm font-medium text-slate-700 mb-2">
                            Additional Info Label
                        </label>
                        <input 
                            type="text" 
                            name="additional_info_label" 
                            id="additional_info_label"
                            value="{{ old('additional_info_label') }}"
                            placeholder="e.g., Expected Date of Delivery"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                    </div>
                </div>
            </div>

            <!-- Order -->
            <div>
                <label for="order" class="block text-sm font-medium text-slate-700 mb-2">
                    Display Order
                </label>
                <input 
                    type="number" 
                    name="order" 
                    id="order"
                    value="{{ old('order', \App\Models\MedicalQuestion::max('order') + 1) }}"
                    min="0"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('order') border-red-300 @enderror"
                >
                <p class="mt-1 text-xs text-slate-500">Lower numbers appear first. Leave empty to add at the end.</p>
                @error('order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Monetary Impact -->
            <div class="border border-slate-200 rounded-lg p-4 bg-blue-50">
                <div class="flex items-center mb-4">
                    <input 
                        type="checkbox" 
                        name="has_monetary_impact" 
                        id="has_monetary_impact" 
                        value="1"
                        {{ old('has_monetary_impact') ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                    >
                    <label for="has_monetary_impact" class="ml-2 block text-sm font-medium text-slate-700">
                        Has Monetary Impact on Policy Calculation
                    </label>
                </div>
                <p class="text-xs text-slate-600 mb-3">When enabled, this question's response will affect premium, deductible, or coverage limits in policy calculations.</p>
                
                <div id="monetary-impact-fields" style="display: {{ old('has_monetary_impact') ? 'block' : 'none' }};">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Impact Type -->
                        <div>
                            <label for="monetary_impact_type" class="block text-sm font-medium text-slate-700 mb-2">
                                Impact Type <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="monetary_impact_type" 
                                id="monetary_impact_type"
                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                                <option value="none" {{ old('monetary_impact_type', 'none') == 'none' ? 'selected' : '' }}>No Impact</option>
                                <option value="premium_adjustment" {{ old('monetary_impact_type') == 'premium_adjustment' ? 'selected' : '' }}>Premium Adjustment</option>
                                <option value="deductible_adjustment" {{ old('monetary_impact_type') == 'deductible_adjustment' ? 'selected' : '' }}>Deductible Adjustment</option>
                                <option value="coverage_limit_adjustment" {{ old('monetary_impact_type') == 'coverage_limit_adjustment' ? 'selected' : '' }}>Coverage Limit Adjustment</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">How this question affects policy calculations</p>
                        </div>

                        <!-- Impact Amount -->
                        <div>
                            <label for="monetary_impact_amount" class="block text-sm font-medium text-slate-700 mb-2">
                                Impact Amount
                            </label>
                            <input 
                                type="number" 
                                name="monetary_impact_amount" 
                                id="monetary_impact_amount"
                                step="0.01"
                                min="0"
                                value="{{ old('monetary_impact_amount') }}"
                                placeholder="e.g., 100000 or 10"
                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                            <p class="mt-1 text-xs text-slate-500">Amount to adjust (UGX if fixed, or percentage if percentage)</p>
                        </div>

                        <!-- Is Percentage -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="monetary_impact_is_percentage" 
                                id="monetary_impact_is_percentage" 
                                value="1"
                                {{ old('monetary_impact_is_percentage') ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                            >
                            <label for="monetary_impact_is_percentage" class="ml-2 block text-sm font-medium text-slate-700">
                                Amount is Percentage (%)
                            </label>
                        </div>

                        <!-- Applies To Response -->
                        <div>
                            <label for="monetary_impact_applies_to_response" class="block text-sm font-medium text-slate-700 mb-2">
                                Applies To Response
                            </label>
                            <input 
                                type="text" 
                                name="monetary_impact_applies_to_response" 
                                id="monetary_impact_applies_to_response"
                                value="{{ old('monetary_impact_applies_to_response', 'yes') }}"
                                placeholder="e.g., yes, no, or specific value"
                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                            <p class="mt-1 text-xs text-slate-500">Which response triggers this impact (e.g., "yes", "no", or specific text/number)</p>
                        </div>

                        <!-- Impact Description -->
                        <div class="md:col-span-2">
                            <label for="monetary_impact_description" class="block text-sm font-medium text-slate-700 mb-2">
                                Impact Description
                            </label>
                            <textarea 
                                name="monetary_impact_description" 
                                id="monetary_impact_description"
                                rows="2"
                                placeholder="Describe how this question affects policy calculations..."
                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >{{ old('monetary_impact_description') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">Optional description of the monetary impact</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    name="is_active" 
                    id="is_active" 
                    value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                >
                <label for="is_active" class="ml-2 block text-sm font-medium text-slate-700">
                    Active (question will be shown in client forms)
                </label>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-200">
                <a href="{{ route('medical-questions.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Create Question
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle exclusion keywords field
    document.getElementById('has_exclusion_list').addEventListener('change', function() {
        document.getElementById('exclusion-keywords-field').style.display = this.checked ? 'block' : 'none';
    });

    // Toggle additional info fields
    document.getElementById('requires_additional_info').addEventListener('change', function() {
        document.getElementById('additional-info-fields').style.display = this.checked ? 'block' : 'none';
    });

    // Toggle monetary impact fields
    document.getElementById('has_monetary_impact').addEventListener('change', function() {
        const fieldsDiv = document.getElementById('monetary-impact-fields');
        const typeSelect = document.getElementById('monetary_impact_type');
        
        if (this.checked) {
            fieldsDiv.style.display = 'block';
            // If type is 'none', automatically set to 'premium_adjustment' as default
            if (typeSelect.value === 'none') {
                typeSelect.value = 'premium_adjustment';
            }
        } else {
            fieldsDiv.style.display = 'none';
            typeSelect.value = 'none';
            // Clear other fields when unchecked
            document.getElementById('monetary_impact_amount').value = '';
            document.getElementById('monetary_impact_is_percentage').checked = false;
            document.getElementById('monetary_impact_applies_to_response').value = 'yes';
            document.getElementById('monetary_impact_description').value = '';
        }
    });
    
    // Also ensure type is not 'none' when checkbox is checked on form submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const hasMonetaryImpact = document.getElementById('has_monetary_impact').checked;
        const monetaryImpactType = document.getElementById('monetary_impact_type').value;
        
        if (hasMonetaryImpact && monetaryImpactType === 'none') {
            e.preventDefault();
            alert('Please select a valid Impact Type when "Has Monetary Impact" is enabled.');
            document.getElementById('monetary_impact_type').focus();
            return false;
        }
    });
</script>
@endsection
