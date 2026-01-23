@extends('layouts.dashboard')

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
            
            @if($plan->description)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-500 mb-1">Description</label>
                <p class="text-sm text-slate-900">{{ $plan->description }}</p>
            </div>
            @endif
        </div>
    </div>

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
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Co-payment</th>
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
                                    @if(($category->pivot->copay_type ?? 'percentage') === 'percentage')
                                        {{ number_format($category->pivot->copay_percentage ?? 0, 2) }}%
                                    @else
                                        UGX {{ number_format($category->pivot->copay_percentage ?? 0, 2) }}
                                    @endif
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
