@extends('layouts.dashboard')

@section('title', 'Edit Medical Question')
@section('page-title', 'Edit Medical Question')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('medical-questions.update', $medicalQuestion) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

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
                >{{ old('question_text', $medicalQuestion->question_text) }}</textarea>
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
                    <option value="yes_no" {{ old('question_type', $medicalQuestion->question_type) == 'yes_no' ? 'selected' : '' }}>Yes/No</option>
                    <option value="text" {{ old('question_type', $medicalQuestion->question_type) == 'text' ? 'selected' : '' }}>Text</option>
                    <option value="date" {{ old('question_type', $medicalQuestion->question_type) == 'date' ? 'selected' : '' }}>Date</option>
                    <option value="number" {{ old('question_type', $medicalQuestion->question_type) == 'number' ? 'selected' : '' }}>Number</option>
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
                        {{ old('has_exclusion_list', $medicalQuestion->has_exclusion_list) ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                    >
                    <label for="has_exclusion_list" class="ml-2 block text-sm font-medium text-slate-700">
                        Include in Exclusion List
                    </label>
                </div>
                <p class="text-xs text-slate-500 mb-3">When enabled, a "YES" response or matching keywords will trigger exclusion list criteria, affecting policy eligibility and claims processing.</p>
                
                <div id="exclusion-keywords-field" style="display: {{ old('has_exclusion_list', $medicalQuestion->has_exclusion_list) ? 'block' : 'none' }};">
                    <label for="exclusion_keywords" class="block text-sm font-medium text-slate-700 mb-2">
                        Exclusion Keywords (comma-separated)
                    </label>
                    <input 
                        type="text" 
                        name="exclusion_keywords" 
                        id="exclusion_keywords"
                        value="{{ old('exclusion_keywords', is_array($medicalQuestion->exclusion_keywords) ? implode(', ', $medicalQuestion->exclusion_keywords) : '') }}"
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
                        {{ old('requires_additional_info', $medicalQuestion->requires_additional_info) ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                    >
                    <label for="requires_additional_info" class="ml-2 block text-sm font-medium text-slate-700">
                        Requires Additional Information
                    </label>
                </div>
                
                <div id="additional-info-fields" style="display: {{ old('requires_additional_info', $medicalQuestion->requires_additional_info) ? 'block' : 'none' }};">
                    <div class="mb-4">
                        <label for="additional_info_type" class="block text-sm font-medium text-slate-700 mb-2">
                            Additional Info Type
                        </label>
                        <select 
                            name="additional_info_type" 
                            id="additional_info_type"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                            <option value="text" {{ old('additional_info_type', $medicalQuestion->additional_info_type) == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="date" {{ old('additional_info_type', $medicalQuestion->additional_info_type) == 'date' ? 'selected' : '' }}>Date</option>
                            <option value="table" {{ old('additional_info_type', $medicalQuestion->additional_info_type) == 'table' ? 'selected' : '' }}>Table (Medications)</option>
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
                            value="{{ old('additional_info_label', $medicalQuestion->additional_info_label) }}"
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
                    value="{{ old('order', $medicalQuestion->order) }}"
                    min="0"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('order') border-red-300 @enderror"
                >
                <p class="mt-1 text-xs text-slate-500">Lower numbers appear first.</p>
                @error('order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    name="is_active" 
                    id="is_active" 
                    value="1"
                    {{ old('is_active', $medicalQuestion->is_active) ? 'checked' : '' }}
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
                    Update Question
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
</script>
@endsection
