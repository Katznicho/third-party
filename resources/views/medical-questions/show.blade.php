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
