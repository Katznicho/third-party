@extends('layouts.dashboard')

@section('title', 'Medical Question Details')
@section('page-title', 'Medical Question Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Question Details</h1>
                <p class="text-slate-600 mt-1">View medical question information</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('medical-questions.edit', $medicalQuestion) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Edit
                </a>
                <a href="{{ route('medical-questions.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                    Back to List
                </a>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Question Text -->
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-2">Question Text</h3>
                <p class="text-base text-slate-900 bg-slate-50 p-4 rounded-lg">{{ $medicalQuestion->question_text }}</p>
            </div>

            <!-- Question Details -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Insurance Company</h3>
                    <p class="text-base text-slate-900">{{ $medicalQuestion->insuranceCompany->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Question Type</h3>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst(str_replace('_', ' ', $medicalQuestion->question_type)) }}
                        </span>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Display Order</h3>
                    <p class="text-base text-slate-900">{{ $medicalQuestion->order }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Status</h3>
                    <p class="text-base text-slate-900">
                        @if($medicalQuestion->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">Inactive</span>
                        @endif
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Exclusion List</h3>
                    <p class="text-base text-slate-900">
                        @if($medicalQuestion->has_exclusion_list)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Enabled</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">Disabled</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Exclusion Keywords -->
            @if($medicalQuestion->has_exclusion_list && !empty($medicalQuestion->exclusion_keywords))
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-2">Exclusion Keywords</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($medicalQuestion->exclusion_keywords as $keyword)
                            <span class="px-3 py-1 text-sm font-medium bg-red-100 text-red-800 rounded-full">
                                {{ $keyword }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 mt-2">A "YES" response or any response containing these keywords will trigger exclusion list criteria.</p>
                </div>
            @endif

            <!-- Additional Info -->
            @if($medicalQuestion->requires_additional_info)
                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-sm font-medium text-slate-500 mb-4">Additional Information Required</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-medium text-slate-500 mb-1">Type</h4>
                            <p class="text-sm text-slate-900">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                    {{ ucfirst($medicalQuestion->additional_info_type) }}
                                </span>
                            </p>
                        </div>
                        @if($medicalQuestion->additional_info_label)
                            <div>
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Label</h4>
                                <p class="text-sm text-slate-900">{{ $medicalQuestion->additional_info_label }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Monetary Impact -->
            <div class="border-t border-slate-200 pt-6">
                <h3 class="text-sm font-medium text-slate-500 mb-4">Monetary Impact on Policy Calculation</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    @if($medicalQuestion->has_monetary_impact && $medicalQuestion->monetary_impact_type !== 'none')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Has Monetary Impact</h4>
                                <p class="text-sm text-slate-900">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                </p>
                            </div>
                            
                            <div>
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Impact Type</h4>
                                <p class="text-sm text-slate-900">
                                    @if($medicalQuestion->monetary_impact_type === 'premium_adjustment')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Premium Adjustment</span>
                                    @elseif($medicalQuestion->monetary_impact_type === 'deductible_adjustment')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Deductible Adjustment</span>
                                    @elseif($medicalQuestion->monetary_impact_type === 'coverage_limit_adjustment')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Coverage Limit Adjustment</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">No Impact</span>
                                    @endif
                                </p>
                            </div>
                            
                            @if($medicalQuestion->monetary_impact_amount)
                            <div>
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Impact Amount</h4>
                                <p class="text-sm text-slate-900 font-semibold">
                                    @if($medicalQuestion->monetary_impact_is_percentage)
                                        {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}%
                                    @else
                                        UGX {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $medicalQuestion->monetary_impact_is_percentage ? 'Percentage-based adjustment' : 'Fixed amount adjustment' }}
                                </p>
                            </div>
                            @endif
                            
                            @if($medicalQuestion->monetary_impact_applies_to_response)
                            <div>
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Applies To Response</h4>
                                <p class="text-sm text-slate-900">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        "{{ $medicalQuestion->monetary_impact_applies_to_response }}"
                                    </span>
                                </p>
                                <p class="text-xs text-slate-500 mt-1">This impact is triggered when the response matches this value</p>
                            </div>
                            @endif
                            
                            @if($medicalQuestion->monetary_impact_description)
                            <div class="md:col-span-2">
                                <h4 class="text-xs font-medium text-slate-500 mb-1">Impact Description</h4>
                                <p class="text-sm text-slate-900 bg-white p-3 rounded border border-slate-200">
                                    {{ $medicalQuestion->monetary_impact_description }}
                                </p>
                            </div>
                            @endif
                        </div>
                        
                        <div class="mt-4 p-3 bg-white rounded border border-blue-300">
                            <p class="text-xs font-medium text-blue-900">
                                <strong>How it works:</strong> When a client responds "{{ $medicalQuestion->monetary_impact_applies_to_response }}" to this question, 
                                @if($medicalQuestion->monetary_impact_type === 'premium_adjustment')
                                    their premium will be 
                                    @if($medicalQuestion->monetary_impact_is_percentage)
                                        adjusted by {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}%
                                    @else
                                        adjusted by UGX {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}
                                    @endif
                                @elseif($medicalQuestion->monetary_impact_type === 'deductible_adjustment')
                                    their deductible will be 
                                    @if($medicalQuestion->monetary_impact_is_percentage)
                                        adjusted by {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}%
                                    @else
                                        adjusted by UGX {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}
                                    @endif
                                @elseif($medicalQuestion->monetary_impact_type === 'coverage_limit_adjustment')
                                    their coverage limits will be 
                                    @if($medicalQuestion->monetary_impact_is_percentage)
                                        adjusted by {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}%
                                    @else
                                        adjusted by UGX {{ number_format($medicalQuestion->monetary_impact_amount, 2) }}
                                    @endif
                                @endif
                                during policy calculation.
                            </p>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-sm text-slate-600">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">No Monetary Impact</span>
                            </p>
                            <p class="text-xs text-slate-500 mt-2">This question does not affect policy premium, deductible, or coverage limits.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Response Statistics -->
            <div class="border-t border-slate-200 pt-6">
                <h3 class="text-sm font-medium text-slate-500 mb-4">Response Statistics</h3>
                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-sm text-slate-600">
                        Total Responses: <span class="font-semibold">{{ $medicalQuestion->responses()->count() }}</span>
                    </p>
                    <p class="text-sm text-slate-600 mt-2">
                        Exclusion Triggers: <span class="font-semibold text-red-600">{{ $medicalQuestion->responses()->where('triggers_exclusion', true)->count() }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
