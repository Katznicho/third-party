@extends('layouts.dashboard')

@section('title', 'Edit Policy')
@section('page-title', 'Edit Policy')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Policy</h1>
            <p class="text-slate-600 mt-1">Update policy information</p>
        </div>
        <a href="{{ route('policies.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Policies
        </a>
    </div>

    <!-- Edit Form -->
    <form action="{{ route('policies.update', $policy) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Policy Details Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Policy Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Policy Number -->
                <div>
                    <label for="policy_number" class="block text-sm font-medium text-slate-700 mb-2">Policy Number <span class="text-red-500">*</span></label>
                    <input type="text" name="policy_number" id="policy_number" value="{{ old('policy_number', $policy->policy_number) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('policy_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Principal Member -->
                <div>
                    <label for="principal_member_id" class="block text-sm font-medium text-slate-700 mb-2">Principal Member <span class="text-red-500">*</span></label>
                    <select name="principal_member_id" id="principal_member_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Principal Member</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('principal_member_id', $policy->principal_member_id) == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }} ({{ $client->id_passport_no }})
                            </option>
                        @endforeach
                    </select>
                    @error('principal_member_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Plan Type -->
                <div>
                    <label for="plan_type" class="block text-sm font-medium text-slate-700 mb-2">Plan Type <span class="text-red-500">*</span></label>
                    <select name="plan_type" id="plan_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Plan Type</option>
                        <option value="Prestige" {{ old('plan_type', $policy->plan_type) === 'Prestige' ? 'selected' : '' }}>Prestige</option>
                        <option value="Executive" {{ old('plan_type', $policy->plan_type) === 'Executive' ? 'selected' : '' }}>Executive</option>
                        <option value="Standard Plus" {{ old('plan_type', $policy->plan_type) === 'Standard Plus' ? 'selected' : '' }}>Standard Plus</option>
                        <option value="Standard" {{ old('plan_type', $policy->plan_type) === 'Standard' ? 'selected' : '' }}>Standard</option>
                        <option value="Regular" {{ old('plan_type', $policy->plan_type) === 'Regular' ? 'selected' : '' }}>Regular</option>
                        <option value="Budget" {{ old('plan_type', $policy->plan_type) === 'Budget' ? 'selected' : '' }}>Budget</option>
                    </select>
                    @error('plan_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="active" {{ old('status', $policy->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $policy->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ old('status', $policy->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="expired" {{ old('status', $policy->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="cancelled" {{ old('status', $policy->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Dates Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Policy Dates</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Inception Date -->
                <div>
                    <label for="inception_date" class="block text-sm font-medium text-slate-700 mb-2">Inception Date <span class="text-red-500">*</span></label>
                    <input type="date" name="inception_date" id="inception_date" value="{{ old('inception_date', $policy->inception_date ? $policy->inception_date->format('Y-m-d') : '') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('inception_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expiry Date -->
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-slate-700 mb-2">Expiry Date <span class="text-red-500">*</span></label>
                    <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $policy->expiry_date ? $policy->expiry_date->format('Y-m-d') : '') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('expiry_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Desired Start Date -->
                <div>
                    <label for="desired_start_date" class="block text-sm font-medium text-slate-700 mb-2">Desired Start Date</label>
                    <input type="date" name="desired_start_date" id="desired_start_date" value="{{ old('desired_start_date', $policy->desired_start_date ? $policy->desired_start_date->format('Y-m-d') : '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('desired_start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Premium Information Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Premium Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Total Premium -->
                <div>
                    <label for="total_premium" class="block text-sm font-medium text-slate-700 mb-2">Total Premium (UGX) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_premium" id="total_premium" value="{{ old('total_premium', $policy->total_premium) }}" step="0.01" min="0" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="calculateTotalPremiumDue()">
                    @error('total_premium')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Insurance Training Levy -->
                <div>
                    <label for="insurance_training_levy" class="block text-sm font-medium text-slate-700 mb-2">Insurance Training Levy (UGX)</label>
                    <input type="number" name="insurance_training_levy" id="insurance_training_levy" value="{{ old('insurance_training_levy', $policy->insurance_training_levy ?? 0) }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="calculateTotalPremiumDue()">
                    @error('insurance_training_levy')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stamp Duty -->
                <div>
                    <label for="stamp_duty" class="block text-sm font-medium text-slate-700 mb-2">Stamp Duty (UGX)</label>
                    <input type="number" name="stamp_duty" id="stamp_duty" value="{{ old('stamp_duty', $policy->stamp_duty ?? 35000) }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="calculateTotalPremiumDue()">
                    @error('stamp_duty')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Total Premium Due -->
                <div>
                    <label for="total_premium_due" class="block text-sm font-medium text-slate-700 mb-2">Total Premium Due (UGX) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_premium_due" id="total_premium_due" value="{{ old('total_premium_due', $policy->total_premium_due) }}" step="0.01" min="0" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" readonly>
                    @error('total_premium_due')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Payment Information Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Payment Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Agent/Broker Name -->
                <div>
                    <label for="agent_broker_name" class="block text-sm font-medium text-slate-700 mb-2">Agent/Broker Name</label>
                    <input type="text" name="agent_broker_name" id="agent_broker_name" value="{{ old('agent_broker_name', $policy->agent_broker_name) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('agent_broker_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Paid -->
                <div class="flex items-center pt-8">
                    <input type="checkbox" name="is_paid" id="is_paid" value="1" {{ old('is_paid', $policy->is_paid) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="is_paid" class="ml-2 block text-sm text-slate-700">Policy is Paid</label>
                </div>

                <!-- Payment Date -->
                <div id="payment-date-field" style="display: {{ old('is_paid', $policy->is_paid) ? 'block' : 'none' }};">
                    <label for="payment_date" class="block text-sm font-medium text-slate-700 mb-2">Payment Date</label>
                    <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', $policy->payment_date ? $policy->payment_date->format('Y-m-d') : '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('payment_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Options Section -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Options</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Has Deductible -->
                <div class="flex items-center">
                    <input type="checkbox" name="has_deductible" id="has_deductible" value="1" {{ old('has_deductible', $policy->has_deductible) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="has_deductible" class="ml-2 block text-sm text-slate-700">Has Deductible</label>
                </div>

                <!-- Telemedicine Only -->
                <div class="flex items-center">
                    <input type="checkbox" name="telemedicine_only" id="telemedicine_only" value="1" {{ old('telemedicine_only', $policy->telemedicine_only) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                    <label for="telemedicine_only" class="ml-2 block text-sm text-slate-700">Telemedicine Only</label>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
            <textarea name="notes" id="notes" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $policy->notes) }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-4 pt-4 border-t border-slate-200">
            <a href="{{ route('policies.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                Update Policy
            </button>
        </div>
    </form>
</div>

<script>
    function calculateTotalPremiumDue() {
        const totalPremium = parseFloat(document.getElementById('total_premium').value) || 0;
        const trainingLevy = parseFloat(document.getElementById('insurance_training_levy').value) || 0;
        const stampDuty = parseFloat(document.getElementById('stamp_duty').value) || 0;
        
        const totalDue = totalPremium + trainingLevy + stampDuty;
        document.getElementById('total_premium_due').value = totalDue.toFixed(2);
    }

    // Show/hide payment date field
    document.getElementById('is_paid').addEventListener('change', function() {
        const paymentDateField = document.getElementById('payment-date-field');
        if (this.checked) {
            paymentDateField.style.display = 'block';
        } else {
            paymentDateField.style.display = 'none';
        }
    });

    // Initialize calculation on page load
    calculateTotalPremiumDue();
</script>
@endsection
