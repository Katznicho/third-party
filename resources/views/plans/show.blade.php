@extends('layouts.dashboard')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Plan Details')
@section('page-title', 'Plan Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $plan->name }}</h1>
            <p class="text-slate-600 mt-1">Plan details and associated products</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('plans.edit', $plan) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Edit Plan
            </a>
            <a href="{{ route('plans.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
                ← Back to Plans
            </a>
        </div>
    </div>

    <!-- Plan Information -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Plan Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Plan Name</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->name }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Code</label>
                <p class="text-sm font-medium text-slate-900">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ $plan->code }}</span>
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Insurance Company</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->insuranceCompany->name ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Status</label>
                <p class="text-sm font-medium text-slate-900">
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Sort Order</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->sort_order ?? 0 }}</p>
            </div>
            
            @if($plan->description)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-500 mb-1">Description</label>
                <p class="text-sm text-slate-900">{{ $plan->description }}</p>
            </div>
            @endif

            @if($plan->image_path)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-500 mb-1">Plan Image</label>
                <img src="{{ Storage::url($plan->image_path) }}" alt="{{ $plan->name }}" class="h-32 w-32 object-cover rounded-lg border border-slate-300">
            </div>
            @endif
        </div>
    </div>

    <!-- Enrollment & Availability -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Enrollment & Availability</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Minimum Enrollment Age</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->min_enrollment_age ? $plan->min_enrollment_age . ' years' : 'No limit' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Maximum Enrollment Age</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->max_enrollment_age ? $plan->max_enrollment_age . ' years' : 'No limit' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Plan Start Date</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->effective_start_date ? $plan->effective_start_date->format('F d, Y') : 'Immediate' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Plan End Date</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->effective_end_date ? $plan->effective_end_date->format('F d, Y') : 'No end date' }}</p>
            </div>
        </div>
    </div>

    <!-- Coverage Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Coverage Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Dependent Coverage Multiplier</label>
                <p class="text-sm font-medium text-slate-900">{{ number_format(($plan->dependent_coverage_multiplier ?? 0.50) * 100, 0) }}%</p>
                <p class="text-xs text-slate-500 mt-1">Dependents pay {{ number_format(($plan->dependent_coverage_multiplier ?? 0.50) * 100, 0) }}% of principal premium</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Annual Maximum Coverage</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->annual_max_coverage ? 'UGX ' . number_format($plan->annual_max_coverage, 2) : 'Unlimited' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Lifetime Maximum Coverage</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->lifetime_max_coverage ? 'UGX ' . number_format($plan->lifetime_max_coverage, 2) : 'Unlimited' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Per Incident Maximum Coverage</label>
                <p class="text-sm font-medium text-slate-900">{{ $plan->per_incident_max_coverage ? 'UGX ' . number_format($plan->per_incident_max_coverage, 2) : 'Unlimited' }}</p>
            </div>
        </div>
    </div>

    <!-- Premium Calculation Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Premium Calculation Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Premium Calculation Method</label>
                <p class="text-sm font-medium text-slate-900">
                    @if(($plan->premium_calculation_method ?? 'benefit_based') === 'benefit_based')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Benefit Based</span>
                    @elseif($plan->premium_calculation_method === 'fixed')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Fixed</span>
                    @elseif($plan->premium_calculation_method === 'hybrid')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Hybrid</span>
                    @endif
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    @if(($plan->premium_calculation_method ?? 'benefit_based') === 'benefit_based')
                        Premium calculated from sum of selected benefits
                    @elseif($plan->premium_calculation_method === 'fixed')
                        Premium uses fixed base amount
                    @elseif($plan->premium_calculation_method === 'hybrid')
                        Premium = Base + Sum of selected benefits
                    @endif
                </p>
            </div>
            
            @if($plan->base_premium)
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Base Premium</label>
                <p class="text-sm font-medium text-slate-900">UGX {{ number_format($plan->base_premium, 2) }}</p>
            </div>
            @endif
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Insurance Training Levy</label>
                <p class="text-sm font-medium text-slate-900">{{ number_format($plan->insurance_training_levy_percentage ?? 0.50, 2) }}%</p>
                <p class="text-xs text-slate-500 mt-1">Applied to subtotal premium</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Stamp Duty</label>
                <p class="text-sm font-medium text-slate-900">UGX {{ number_format($plan->stamp_duty_amount ?? 35000, 2) }}</p>
                <p class="text-xs text-slate-500 mt-1">Fixed amount added to total premium</p>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions -->
    @if($plan->terms_and_conditions || $plan->terms_link)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Terms & Conditions</h2>
        
        <div class="space-y-4">
            @if($plan->terms_link)
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Terms Link</label>
                <a href="{{ $plan->terms_link }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-900 underline">{{ $plan->terms_link }}</a>
            </div>
            @endif
            
            @if($plan->terms_and_conditions)
            <div>
                <label class="block text-sm font-medium text-slate-500 mb-1">Terms & Conditions</label>
                <div class="text-sm text-slate-900 whitespace-pre-wrap bg-slate-50 p-4 rounded-lg border border-slate-200">{{ $plan->terms_and_conditions }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Associated Products -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Associated Products ({{ $plan->serviceCategories->count() }})</h2>
        
        @if($plan->serviceCategories->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Benefit Amount (UGX)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Base Amount (UGX)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Waiting Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($plan->serviceCategories as $category)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $category->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $category->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $category->pivot->benefit_amount ? number_format($category->pivot->benefit_amount, 2) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $category->pivot->base_amount ? number_format($category->pivot->base_amount, 2) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $category->pivot->waiting_period_days ?? 0 }} days</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $category->pivot->is_enabled ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                        {{ $category->pivot->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-sm text-slate-500">No products associated with this plan.</p>
                <a href="{{ route('plans.edit', $plan) }}" class="mt-4 inline-block text-blue-600 hover:text-blue-900 text-sm font-medium">
                    Add Products →
                </a>
            </div>
        @endif
    </div>

    <!-- Clients with this Plan -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Clients with this Plan ({{ $plan->clients->count() }})</h2>
        
        @if($plan->clients->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID/Passport</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($plan->clients as $client)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $client->full_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $client->id_passport_no ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $client->cell_phone ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $client->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-sm text-slate-500">No clients are currently assigned to this plan.</p>
            </div>
        @endif
    </div>
</div>
@endsection
