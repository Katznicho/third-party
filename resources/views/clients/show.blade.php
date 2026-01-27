@extends('layouts.dashboard')

@section('title', 'Client Details')
@section('page-title', 'Client Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Client Details</h1>
            <p class="text-slate-600 mt-1">View complete client information and policy details</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Client
            </a>
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                ← Back to Clients
            </a>
        </div>
    </div>

    <!-- Client Information -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Personal Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Full Name</label>
                <p class="text-base text-slate-900 font-semibold">{{ $client->full_name }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">ID/Passport Number</label>
                <p class="text-base text-slate-900">{{ $client->id_passport_no }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Gender</label>
                <p class="text-base text-slate-900">{{ $client->gender ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Date of Birth</label>
                <p class="text-base text-slate-900">{{ $client->date_of_birth ? $client->date_of_birth->format('d M Y') : 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Marital Status</label>
                <p class="text-base text-slate-900">{{ $client->marital_status ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Email</label>
                <p class="text-base text-slate-900">{{ $client->email ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Cell Phone</label>
                <p class="text-base text-slate-900">{{ $client->cell_phone ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Type</label>
                <p class="text-base text-slate-900">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $client->type === 'principal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ ucfirst($client->type) }}
                    </span>
                </p>
            </div>
            
            @if($client->isDependent())
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Principal Member</label>
                <p class="text-base text-slate-900">
                    <a href="{{ route('clients.show', $client->principalMember) }}" class="text-blue-600 hover:text-blue-800">
                        {{ $client->principalMember->full_name ?? 'N/A' }}
                    </a>
                </p>
            </div>
            @endif
        </div>
    </div>

    <!-- Policy Information -->
    @if($client->isPrincipal() && $client->policies->isNotEmpty())
        @foreach($client->policies as $policy)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Policy Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Policy Number</label>
                    <p class="text-base text-slate-900 font-bold text-blue-600">{{ $policy->policy_number }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Plan Type</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                            {{ $policy->plan_type }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Status</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($policy->status) }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Inception Date</label>
                    <p class="text-base text-slate-900">{{ $policy->inception_date->format('d M Y') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Expiry Date</label>
                    <p class="text-base text-slate-900">{{ $policy->expiry_date->format('d M Y') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Payment Status</label>
                    <p class="text-base text-slate-900">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $policy->is_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $policy->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Premium Details -->
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Premium Details</h3>
                <div class="bg-slate-50 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Total Premium:</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->total_premium, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Insurance Training Levy (0.5%):</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->insurance_training_levy, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Stamp Duty:</span>
                        <span class="text-sm font-bold text-slate-900">UGX {{ number_format($policy->stamp_duty, 2) }}</span>
                    </div>
                    <div class="border-t-2 border-blue-500 pt-3 flex justify-between items-center">
                        <span class="text-base font-bold text-slate-900">Total Premium Due:</span>
                        <span class="text-lg font-bold text-blue-600">UGX {{ number_format($policy->total_premium_due, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Policy Options -->
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Policy Options</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($policy->has_deductible)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-yellow-800 mb-1">Deductible</label>
                        <p class="text-sm text-yellow-900">
                            @if($policy->deductible_amount)
                                UGX {{ number_format($policy->deductible_amount, 2) }}
                            @else
                                Enabled
                            @endif
                        </p>
                    </div>
                    @endif
                    
                    @if($policy->copay_amount)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-blue-800 mb-1">Co-payment</label>
                        <p class="text-sm text-blue-900">
                            UGX {{ number_format($policy->copay_amount, 2) }} per visit
                            @if($policy->copay_max_limit)
                                <br><span class="text-xs">Max Limit: UGX {{ number_format($policy->copay_max_limit, 2) }}</span>
                            @endif
                        </p>
                    </div>
                    @endif
                    
                    @if($policy->coinsurance_percentage)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-indigo-800 mb-1">Coinsurance</label>
                        <p class="text-sm text-indigo-900">{{ number_format($policy->coinsurance_percentage, 2) }}%</p>
                    </div>
                    @endif
                    
                    @if($policy->telemedicine_only)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-green-800 mb-1">Telemedicine Only</label>
                        <p class="text-sm text-green-900">Enabled</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Policy Benefits -->
            @if($policy->benefits->isNotEmpty())
            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Policy Benefits</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Service Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Benefit Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Used Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Remaining Amount</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($policy->benefits as $benefit)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $benefit->serviceCategory->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900 text-right">UGX {{ number_format($benefit->benefit_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 text-right">UGX {{ number_format($benefit->used_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900 text-right">UGX {{ number_format($benefit->remaining_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $benefit->is_enabled ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $benefit->is_enabled ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    @endif

    <!-- Dependents -->
    @if($client->isPrincipal() && $client->dependents->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Dependents</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">ID/Passport</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Relation</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date of Birth</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($client->dependents as $dependent)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->id_passport_no }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->relation_to_principal ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $dependent->date_of_birth ? $dependent->date_of_birth->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('clients.show', $dependent) }}" class="text-blue-600 hover:text-blue-800">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Medical History Responses -->
    @if($client->medicalQuestionResponses->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Medical Questionnaire Responses</h2>
        <div class="space-y-4">
            @foreach($client->medicalQuestionResponses as $response)
            <div class="border border-slate-200 rounded-lg p-4 {{ $response->triggers_exclusion ? 'bg-red-50 border-red-300' : '' }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $response->question->question_text }}</p>
                        <p class="text-sm text-slate-600 mt-1">
                            <strong>Response:</strong> {{ ucfirst($response->response ?? 'N/A') }}
                        </p>
                        @if($response->additional_info)
                            <p class="text-xs text-slate-500 mt-1">
                                <strong>Additional Info:</strong> {{ is_array($response->additional_info) ? json_encode($response->additional_info) : $response->additional_info }}
                            </p>
                        @endif
                    </div>
                    @if($response->triggers_exclusion)
                        <span class="ml-4 px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">Exclusion Triggered</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
