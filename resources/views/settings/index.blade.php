@extends('layouts.dashboard')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
            <p class="text-slate-600 mt-1">Configure your insurance company settings</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200">
            <nav class="flex -mb-px" aria-label="Tabs">
                <button 
                    onclick="switchTab('policy-number')"
                    id="tab-policy-number"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-blue-500 text-blue-600"
                >
                    Policy Numbers
                </button>
                <button 
                    onclick="switchTab('deductible')"
                    id="tab-deductible"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Deductible
                </button>
                <button 
                    onclick="switchTab('client-fields')"
                    id="tab-client-fields"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Client Fields
                </button>
                <button 
                    onclick="switchTab('verification')"
                    id="tab-verification"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Verification
                </button>
                <button 
                    onclick="switchTab('coverage')"
                    id="tab-coverage"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Coverage & Pre-Auth
                </button>
                <button 
                    onclick="switchTab('authorization')"
                    id="tab-authorization"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Authorization
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Policy Number Generation Tab -->
            <div id="content-policy-number" class="tab-content">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Policy Number Generation</h2>
                    <p class="text-sm text-slate-600 mb-6">Configure how policy numbers are generated for new policies.</p>

                    <form action="{{ route('settings.update-policy-number') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Policy Number Format -->
                        <div>
                            <label for="policy_number_format" class="block text-sm font-medium text-slate-700 mb-2">
                                Policy Number Format
                            </label>
                            <input 
                                type="text" 
                                name="policy_number_format" 
                                id="policy_number_format" 
                                value="{{ old('policy_number_format', $insuranceCompany->policy_number_format ?? '{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}') }}"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}"
                                required
                            >
                            <p class="text-xs text-slate-500 mt-2">
                                Available placeholders: <code class="bg-slate-100 px-1 py-0.5 rounded">{COMPANY}</code>, 
                                <code class="bg-slate-100 px-1 py-0.5 rounded">{YEAR}</code>, 
                                <code class="bg-slate-100 px-1 py-0.5 rounded">{YEAR2}</code>, 
                                <code class="bg-slate-100 px-1 py-0.5 rounded">{MONTH}</code>, 
                                <code class="bg-slate-100 px-1 py-0.5 rounded">{DAY}</code>, 
                                <code class="bg-slate-100 px-1 py-0.5 rounded">{RANDOM}</code>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Example: <code class="bg-slate-100 px-1 py-0.5 rounded">{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}</code> 
                                generates: <code class="bg-slate-100 px-1 py-0.5 rounded">AAR-20260203-ABC123</code>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Random Part Length -->
                            <div>
                                <label for="policy_number_random_length" class="block text-sm font-medium text-slate-700 mb-2">
                                    Random Part Length
                                </label>
                                <input 
                                    type="number" 
                                    name="policy_number_random_length" 
                                    id="policy_number_random_length" 
                                    value="{{ old('policy_number_random_length', $insuranceCompany->policy_number_random_length ?? 6) }}"
                                    min="3"
                                    max="12"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                <p class="text-xs text-slate-500 mt-1">Number of characters in the random part (3-12)</p>
                            </div>

                            <!-- Random Part Type -->
                            <div>
                                <label for="policy_number_random_type" class="block text-sm font-medium text-slate-700 mb-2">
                                    Random Part Type
                                </label>
                                <select 
                                    name="policy_number_random_type" 
                                    id="policy_number_random_type" 
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                    <option value="alphanumeric" {{ old('policy_number_random_type', $insuranceCompany->policy_number_random_type ?? 'alphanumeric') === 'alphanumeric' ? 'selected' : '' }}>
                                        Alphanumeric (A-Z, 0-9)
                                    </option>
                                    <option value="numeric" {{ old('policy_number_random_type', $insuranceCompany->policy_number_random_type ?? 'alphanumeric') === 'numeric' ? 'selected' : '' }}>
                                        Numeric (0-9)
                                    </option>
                                    <option value="alphabetic" {{ old('policy_number_random_type', $insuranceCompany->policy_number_random_type ?? 'alphanumeric') === 'alphabetic' ? 'selected' : '' }}>
                                        Alphabetic (A-Z)
                                    </option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Type of characters for random part</p>
                            </div>

                            <!-- Company Code Length -->
                            <div>
                                <label for="policy_number_company_code_length" class="block text-sm font-medium text-slate-700 mb-2">
                                    Company Code Length
                                </label>
                                <input 
                                    type="number" 
                                    name="policy_number_company_code_length" 
                                    id="policy_number_company_code_length" 
                                    value="{{ old('policy_number_company_code_length', $insuranceCompany->policy_number_company_code_length ?? 3) }}"
                                    min="1"
                                    max="8"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                <p class="text-xs text-slate-500 mt-1">Number of characters from company code to use (1-8)</p>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">Preview</h3>
                            <p class="text-sm text-blue-800">
                                Example policy number: 
                                <code class="bg-white px-2 py-1 rounded font-mono text-blue-900" id="preview-policy-number">
                                    {{ $insuranceCompany->code ? strtoupper(substr($insuranceCompany->code, 0, $insuranceCompany->policy_number_company_code_length ?? 3)) : 'COMP' }}-{{ now()->format('Ymd') }}-{{ strtoupper(\Illuminate\Support\Str::random($insuranceCompany->policy_number_random_length ?? 6)) }}
                                </code>
                            </p>
                            <p class="text-xs text-blue-600 mt-2">This is a preview. Actual policy numbers will be generated when creating new policies.</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-4 border-t border-slate-200">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Deductible Contribution Tab -->
            <div id="content-deductible" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Deductible Contribution Settings</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure whether copay and coinsurance payments contribute towards meeting the deductible. 
                        These are default settings that apply to all new policies. Individual policies can override these settings.
                    </p>

                    <form action="{{ route('settings.update-deductible-contribution') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <!-- Copay Contribution -->
                            <div class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="copay_contributes_to_deductible" 
                                    id="copay_contributes_to_deductible" 
                                    value="1"
                                    {{ old('copay_contributes_to_deductible', $insuranceCompany->copay_contributes_to_deductible ?? false) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                >
                                <div class="ml-3">
                                    <label for="copay_contributes_to_deductible" class="block text-sm font-medium text-slate-700">
                                        Copay contributes to deductible
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1">
                                        When enabled, copay amounts paid by clients will count towards meeting their deductible. 
                                        For example, if a client has a UGX 100,000 deductible and pays UGX 10,000 in copays, 
                                        only UGX 90,000 remains to meet the deductible.
                                    </p>
                                </div>
                            </div>

                            <!-- Coinsurance Contribution -->
                            <div class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="coinsurance_contributes_to_deductible" 
                                    id="coinsurance_contributes_to_deductible" 
                                    value="1"
                                    {{ old('coinsurance_contributes_to_deductible', $insuranceCompany->coinsurance_contributes_to_deductible ?? false) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                >
                                <div class="ml-3">
                                    <label for="coinsurance_contributes_to_deductible" class="block text-sm font-medium text-slate-700">
                                        Coinsurance contributes to deductible
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1">
                                        When enabled, coinsurance amounts paid by clients will count towards meeting their deductible. 
                                        For example, if a client has a UGX 100,000 deductible and pays UGX 15,000 in coinsurance, 
                                        only UGX 85,000 remains to meet the deductible.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">How it works:</h3>
                            <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                                <li>These settings are <strong>default values</strong> for all new policies created after saving.</li>
                                <li>Existing policies are not automatically updated.</li>
                                <li>Individual policies can override these settings during creation or editing.</li>
                                <li>When both copay and coinsurance contribute, both amounts are counted towards the deductible.</li>
                            </ul>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-4 border-t border-slate-200">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Required Client Fields Tab -->
            <div id="content-client-fields" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Required Client Fields</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure which fields are required when creating clients. Different insurance companies may require different information. 
                        For example, Prudential may require more detailed information than Jubilee.
                    </p>

                    <form action="{{ route('settings.update-required-client-fields') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @php
                            $defaultFields = \App\Models\InsuranceCompany::getDefaultRequiredFields();
                            $currentFields = $insuranceCompany->required_client_fields ?? [];
                            $fieldLabels = [
                                'surname' => 'Surname',
                                'first_name' => 'First Name',
                                'other_names' => 'Other Names',
                                'title' => 'Title',
                                'id_passport_no' => 'ID/Passport Number',
                                'gender' => 'Gender',
                                'tin' => 'TIN',
                                'date_of_birth' => 'Date of Birth',
                                'marital_status' => 'Marital Status',
                                'height' => 'Height',
                                'weight' => 'Weight',
                                'employer_name' => 'Employer Name',
                                'occupation' => 'Occupation',
                                'nationality' => 'Nationality',
                                'home_physical_address' => 'Home Physical Address',
                                'office_physical_address' => 'Office Physical Address',
                                'home_telephone' => 'Home Telephone',
                                'office_telephone' => 'Office Telephone',
                                'cell_phone' => 'Cell Phone',
                                'whatsapp_line' => 'WhatsApp Line',
                                'email' => 'Email',
                                'next_of_kin_surname' => 'Next of Kin Surname',
                                'next_of_kin_first_name' => 'Next of Kin First Name',
                                'next_of_kin_other_names' => 'Next of Kin Other Names',
                                'next_of_kin_title' => 'Next of Kin Title',
                                'next_of_kin_relation' => 'Next of Kin Relation',
                                'next_of_kin_id_passport_no' => 'Next of Kin ID/Passport',
                                'next_of_kin_cell_phone' => 'Next of Kin Cell Phone',
                                'next_of_kin_email' => 'Next of Kin Email',
                                'next_of_kin_post_address' => 'Next of Kin Postal Address',
                                'next_of_kin_physical_address' => 'Next of Kin Physical Address',
                            ];
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($defaultFields as $fieldName => $defaultValue)
                                @php
                                    $isRequired = isset($currentFields[$fieldName]) 
                                        ? (bool)$currentFields[$fieldName] 
                                        : $defaultValue;
                                    $label = $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
                                @endphp
                                <label class="flex items-center p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="required_fields[{{ $fieldName }}]" 
                                        value="1"
                                        {{ $isRequired ? 'checked' : '' }}
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                                    >
                                    <span class="ml-2 text-sm text-slate-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">How it works:</h3>
                            <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                                <li>Checked fields will be <strong>required</strong> when creating or editing clients.</li>
                                <li>Unchecked fields will be <strong>optional</strong>.</li>
                                <li>These settings apply to all new client creation forms.</li>
                                <li><strong>Note:</strong> First Name and ID/Passport Number are always required by default and cannot be unchecked.</li>
                            </ul>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-4 border-t border-slate-200">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Identity Verification Tab -->
            <div id="content-verification" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Identity Verification Settings</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure verification methods. You can choose which methods to enable and whether Physical National ID is required. Two-step verification is recommended for security.
                    </p>

                    <form action="{{ route('settings.update-verification') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Physical ID Requirement -->
                        <div class="border border-slate-200 rounded-lg p-5 bg-blue-50">
                            <div class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="require_physical_id" 
                                    id="require_physical_id"
                                    value="1"
                                    {{ old('require_physical_id', $insuranceCompany->require_physical_id ?? true) ? 'checked' : '' }}
                                    class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                >
                                <div class="ml-3 flex-1">
                                    <label for="require_physical_id" class="block text-base font-semibold text-slate-900 cursor-pointer">
                                        Require Physical National ID Verification
                                    </label>
                                    <p class="text-sm text-slate-600 mt-1">
                                        When enabled, all verification methods will require Physical National ID as Step 1. When disabled, methods can be used without Physical ID requirement.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Methods -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">Verification Methods</h3>
                            <p class="text-sm text-slate-600 mb-4">Select which verification methods to enable for your insurance company.</p>
                            
                            <!-- Method 1: Physical ID + Policy # + Matching Details -->
                            <div class="border border-slate-200 rounded-lg p-5 bg-slate-50">
                                <div class="flex items-start mb-3">
                                    <input 
                                        type="checkbox" 
                                        name="enable_method_1" 
                                        id="enable_method_1"
                                        value="1"
                                        {{ old('enable_method_1', $insuranceCompany->enable_method_1 ?? true) ? 'checked' : '' }}
                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                    >
                                    <div class="ml-3 flex-1">
                                        <label for="enable_method_1" class="block text-base font-semibold text-slate-900 cursor-pointer">
                                            Method 1: Policy Number + Matching Details
                                        </label>
                                        <p class="text-sm text-slate-600 mt-1">
                                            Requires: Policy Number + matching name, date of birth, and other details. <span id="method1-physical-id-note" class="font-medium">Physical National ID is also required if enabled above.</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-8 space-y-3 mt-4" id="method1-steps">
                                    <div class="bg-white rounded p-3 border border-slate-200" id="method1-step1-physical-id" style="display: none;">
                                        <p class="text-xs font-medium text-slate-700 mb-2">Step 1: Physical National ID</p>
                                        <p class="text-xs text-slate-600">Client must provide their physical national ID/Passport for verification.</p>
                                    </div>
                                    <div class="bg-white rounded p-3 border border-slate-200">
                                        <p class="text-xs font-medium text-slate-700 mb-2" id="method1-step-label">Step <span id="method1-step-num">1</span>: Policy Number + Matching Details</p>
                                        <p class="text-xs text-slate-600">Client must provide policy number and matching details (name, date of birth, etc.) that match the policy records.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Method 2: Physical ID + Phone OTP -->
                            <div class="border border-slate-200 rounded-lg p-5 bg-slate-50">
                                <div class="flex items-start mb-3">
                                    <input 
                                        type="checkbox" 
                                        name="enable_method_2" 
                                        id="enable_method_2"
                                        value="1"
                                        {{ old('enable_method_2', $insuranceCompany->enable_method_2 ?? false) ? 'checked' : '' }}
                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                    >
                                    <div class="ml-3 flex-1">
                                        <label for="enable_method_2" class="block text-base font-semibold text-slate-900 cursor-pointer">
                                            Method 2: Phone OTP
                                        </label>
                                        <p class="text-sm text-slate-600 mt-1">
                                            Requires: Phone OTP sent to registered phone number. <span id="method2-physical-id-note" class="font-medium">Physical National ID is also required if enabled above.</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-8 space-y-3 mt-4" id="method2-steps">
                                    <div class="bg-white rounded p-3 border border-slate-200" id="method2-step1-physical-id" style="display: none;">
                                        <p class="text-xs font-medium text-slate-700 mb-2">Step 1: Physical National ID</p>
                                        <p class="text-xs text-slate-600">Client must provide their physical national ID/Passport for verification.</p>
                                    </div>
                                    <div class="bg-white rounded p-3 border border-slate-200">
                                        <p class="text-xs font-medium text-slate-700 mb-2" id="method2-step-label">Step <span id="method2-step-num">1</span>: Phone OTP</p>
                                        <p class="text-xs text-slate-600">An OTP code is sent to the client's registered phone number. Client must enter the correct OTP to complete verification.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Method 3: Physical ID + Email + OTP -->
                            <div class="border border-slate-200 rounded-lg p-5 bg-slate-50">
                                <div class="flex items-start mb-3">
                                    <input 
                                        type="checkbox" 
                                        name="enable_method_3" 
                                        id="enable_method_3"
                                        value="1"
                                        {{ old('enable_method_3', $insuranceCompany->enable_method_3 ?? false) ? 'checked' : '' }}
                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                    >
                                    <div class="ml-3 flex-1">
                                        <label for="enable_method_3" class="block text-base font-semibold text-slate-900 cursor-pointer">
                                            Method 3: Email + OTP
                                        </label>
                                        <p class="text-sm text-slate-600 mt-1">
                                            Requires: Email OTP sent to registered email address. <span id="method3-physical-id-note" class="font-medium">Physical National ID is also required if enabled above.</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-8 space-y-3 mt-4" id="method3-steps">
                                    <div class="bg-white rounded p-3 border border-slate-200" id="method3-step1-physical-id" style="display: none;">
                                        <p class="text-xs font-medium text-slate-700 mb-2">Step 1: Physical National ID</p>
                                        <p class="text-xs text-slate-600">Client must provide their physical national ID/Passport for verification.</p>
                                    </div>
                                    <div class="bg-white rounded p-3 border border-slate-200">
                                        <p class="text-xs font-medium text-slate-700 mb-2" id="method3-step-label">Step <span id="method3-step-num">1</span>: Email + OTP</p>
                                        <p class="text-xs text-slate-600">An OTP code is sent to the client's registered email address. Client must enter the correct OTP to complete verification.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Method 4: Physical ID + Name + Date of Birth -->
                            <div class="border border-slate-200 rounded-lg p-5 bg-slate-50">
                                <div class="flex items-start mb-3">
                                    <input 
                                        type="checkbox" 
                                        name="enable_method_4" 
                                        id="enable_method_4"
                                        value="1"
                                        {{ old('enable_method_4', $insuranceCompany->enable_method_4 ?? false) ? 'checked' : '' }}
                                        class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                    >
                                    <div class="ml-3 flex-1">
                                        <label for="enable_method_4" class="block text-base font-semibold text-slate-900 cursor-pointer">
                                            Method 4: Name + Date of Birth
                                        </label>
                                        <p class="text-sm text-slate-600 mt-1">
                                            Requires: Name and Date of Birth matching policy records. <span id="method4-physical-id-note" class="font-medium">Physical National ID is also required if enabled above.</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-8 space-y-3 mt-4" id="method4-steps">
                                    <div class="bg-white rounded p-3 border border-slate-200" id="method4-step1-physical-id" style="display: none;">
                                        <p class="text-xs font-medium text-slate-700 mb-2">Step 1: Physical National ID</p>
                                        <p class="text-xs text-slate-600">Client must provide their physical national ID/Passport for verification.</p>
                                    </div>
                                    <div class="bg-white rounded p-3 border border-slate-200">
                                        <p class="text-xs font-medium text-slate-700 mb-2" id="method4-step-label">Step <span id="method4-step-num">1</span>: Name + Date of Birth</p>
                                        <p class="text-xs text-slate-600">Client must provide their full name and date of birth that match the policy records. Name similarity and DOB tolerance settings apply.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Method 2 & 3 Settings -->
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">OTP Settings (Methods 2 & 3)</h3>
                            <p class="text-sm text-slate-600 mb-4">Configure OTP settings for Phone and Email verification methods.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="phone_otp_expiry_minutes" class="block text-sm font-medium text-slate-700 mb-2">
                                        Phone OTP Expiry (Minutes)
                                    </label>
                                    <input 
                                        type="number" 
                                        name="phone_otp_expiry_minutes" 
                                        id="phone_otp_expiry_minutes"
                                        value="{{ old('phone_otp_expiry_minutes', $insuranceCompany->phone_otp_expiry_minutes ?? 10) }}"
                                        min="1"
                                        max="60"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                    <p class="text-xs text-slate-500 mt-1">How long the phone OTP remains valid (1-60 minutes)</p>
                                </div>

                                <div>
                                    <label for="email_otp_expiry_minutes" class="block text-sm font-medium text-slate-700 mb-2">
                                        Email OTP Expiry (Minutes)
                                    </label>
                                    <input 
                                        type="number" 
                                        name="email_otp_expiry_minutes" 
                                        id="email_otp_expiry_minutes"
                                        value="{{ old('email_otp_expiry_minutes', $insuranceCompany->email_otp_expiry_minutes ?? 15) }}"
                                        min="1"
                                        max="60"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                    <p class="text-xs text-slate-500 mt-1">How long the email OTP remains valid (1-60 minutes)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mismatch Handling Rules -->
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">Mismatch Handling Rules</h3>
                            <p class="text-sm text-slate-600 mb-4">Configure how to handle mismatches between provided information and policy records. These settings apply when name, date of birth, or ID/Passport verification is required in any method.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="name_mismatch_action" class="block text-sm font-medium text-slate-700 mb-2">
                                        Name Mismatch Action
                                    </label>
                                    <select 
                                        name="name_mismatch_action" 
                                        id="name_mismatch_action"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="flag_for_review" {{ old('name_mismatch_action', $insuranceCompany->name_mismatch_action ?? 'flag_for_review') === 'flag_for_review' ? 'selected' : '' }}>
                                            Flag for Review
                                        </option>
                                        <option value="auto_reject" {{ old('name_mismatch_action', $insuranceCompany->name_mismatch_action ?? 'flag_for_review') === 'auto_reject' ? 'selected' : '' }}>
                                            Auto Reject
                                        </option>
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1">Action when name doesn't match policy records</p>
                                </div>

                                <div>
                                    <label for="dob_mismatch_action" class="block text-sm font-medium text-slate-700 mb-2">
                                        Date of Birth Mismatch Action
                                    </label>
                                    <select 
                                        name="dob_mismatch_action" 
                                        id="dob_mismatch_action"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="flag_for_review" {{ old('dob_mismatch_action', $insuranceCompany->dob_mismatch_action ?? 'flag_for_review') === 'flag_for_review' ? 'selected' : '' }}>
                                            Flag for Review
                                        </option>
                                        <option value="auto_reject" {{ old('dob_mismatch_action', $insuranceCompany->dob_mismatch_action ?? 'flag_for_review') === 'auto_reject' ? 'selected' : '' }}>
                                            Auto Reject
                                        </option>
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1">Action when date of birth doesn't match</p>
                                </div>

                                <div>
                                    <label for="id_mismatch_action" class="block text-sm font-medium text-slate-700 mb-2">
                                        ID/Passport Mismatch Action
                                    </label>
                                    <select 
                                        name="id_mismatch_action" 
                                        id="id_mismatch_action"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="flag_for_review" {{ old('id_mismatch_action', $insuranceCompany->id_mismatch_action ?? 'flag_for_review') === 'flag_for_review' ? 'selected' : '' }}>
                                            Flag for Review
                                        </option>
                                        <option value="auto_reject" {{ old('id_mismatch_action', $insuranceCompany->id_mismatch_action ?? 'flag_for_review') === 'auto_reject' ? 'selected' : '' }}>
                                            Auto Reject
                                        </option>
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1">Action when ID/Passport doesn't match</p>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Tolerance Settings -->
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">Verification Tolerance Settings</h3>
                            <p class="text-sm text-slate-600 mb-4">Configure tolerance settings for name and date of birth matching. These settings can be used across all verification methods when name or date of birth verification is required.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name_similarity_threshold" class="block text-sm font-medium text-slate-700 mb-2">
                                        Name Similarity Threshold (%)
                                    </label>
                                    <input 
                                        type="number" 
                                        name="name_similarity_threshold" 
                                        id="name_similarity_threshold"
                                        value="{{ old('name_similarity_threshold', $insuranceCompany->name_similarity_threshold ?? 80) }}"
                                        min="0"
                                        max="100"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                    <p class="text-xs text-slate-500 mt-1">Minimum similarity percentage for name matching (0-100). Used when name verification is required in any method.</p>
                                </div>

                                <div>
                                    <label for="dob_tolerance_days" class="block text-sm font-medium text-slate-700 mb-2">
                                        Date of Birth Tolerance (Days)
                                    </label>
                                    <input 
                                        type="number" 
                                        name="dob_tolerance_days" 
                                        id="dob_tolerance_days"
                                        value="{{ old('dob_tolerance_days', $insuranceCompany->dob_tolerance_days ?? 0) }}"
                                        min="0"
                                        max="365"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                    <p class="text-xs text-slate-500 mt-1">Days tolerance for date of birth matching. Used when date of birth verification is required in any method.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">How it works:</h3>
                            <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                                <li><strong>Physical ID Requirement:</strong> You can choose whether Physical National ID is required for all methods. When enabled, it becomes Step 1 for all verification methods.</li>
                                <li><strong>Method 1:</strong> Policy Number + matching details (name, DOB, etc.) + Physical ID (if required)</li>
                                <li><strong>Method 2:</strong> Phone OTP sent to registered phone number + Physical ID (if required)</li>
                                <li><strong>Method 3:</strong> Email OTP sent to registered email address + Physical ID (if required)</li>
                                <li><strong>Method 4:</strong> Name + Date of Birth matching policy records + Physical ID (if required)</li>
                                <li><strong>Flag for Review:</strong> Verification is flagged for manual review by insurance company staff when mismatches occur.</li>
                                <li><strong>Auto Reject:</strong> Verification is automatically rejected when mismatches exceed tolerance levels.</li>
                                <li>Name similarity uses fuzzy matching to handle minor spelling differences.</li>
                                <li>OTP codes expire after the configured time period for security.</li>
                            </ul>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-4 border-t border-slate-200">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Coverage & Pre-Authorization Tab -->
            <div id="content-coverage" class="tab-content" style="display: none;">
                <div class="space-y-6">
                    <!-- Quick Access Cards -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-xl font-bold text-slate-900 mb-4">Coverage & Pre-Authorization Settings</h2>
                        <p class="text-sm text-slate-600 mb-6">
                            Configure automatic decision rules for coverage matching and pre-authorization triggers for high-cost services, special procedures, and keyword-based events.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Coverage Decision Matrix -->
                            <div class="border border-slate-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-slate-800 mb-2">Coverage Decision Matrix</h3>
                                <p class="text-sm text-slate-600 mb-4">
                                    Define rules for automatic rejection or manual review when coverage doesn't match (e.g., OPD not covered, cost thresholds exceeded).
                                </p>
                                <a href="{{ route('settings.coverage-decision-matrix.index') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    Manage Decision Rules
                                </a>
                            </div>

                            <!-- Pre-Authorization Triggers -->
                            <div class="border border-slate-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-slate-800 mb-2">Pre-Authorization Triggers</h3>
                                <p class="text-sm text-slate-600 mb-4">
                                    Configure automatic pre-authorization triggers for high-cost services, special procedures, and keyword-triggered events.
                                </p>
                                <a href="{{ route('settings.pre-authorization-triggers.index') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                    Manage Triggers
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Explanation -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-xl font-bold text-slate-900 mb-4">How It Works - Detailed Guide</h2>
                        
                        <!-- Coverage Decision Matrix Explanation -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Coverage Decision Matrix
                            </h3>
                            <p class="text-sm text-slate-700 mb-4">
                                The <strong>Coverage Decision Matrix</strong> automatically evaluates invoices/services when they're created and makes decisions based on your rules. Rules are checked in priority order (lower priority number = checked first).
                            </p>
                            
                            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-slate-800 mb-2">When does it run?</h4>
                                <p class="text-sm text-slate-700 mb-3">Every time an invoice or service is created in Kashtre for one of your clients.</p>
                                
                                <h4 class="font-semibold text-slate-800 mb-2 mt-4">What happens?</h4>
                                <ol class="text-sm text-slate-700 space-y-2 list-decimal list-inside">
                                    <li>System checks your rules in priority order (1, 2, 3, etc.)</li>
                                    <li>If a rule matches, it takes the action you defined (reject, flag for review, or require pre-auth)</li>
                                    <li>If no rules match, it checks if the service is covered by the policy</li>
                                    <li>If covered and within limits, it's approved automatically</li>
                                </ol>
                            </div>

                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-green-900 mb-3">Example Rules for Whysemedical (BPXX4Q9):</h4>
                                
                                <div class="space-y-4">
                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Rule 1: Reject OPD Services</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Condition:</strong> Service category is "OPD" (Outpatient Department)</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Action:</strong> Auto Reject</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Message:</strong> "OPD services are not covered under this policy."</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 10 (checked first)</p>
                                    </div>

                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Rule 2: Flag High-Cost Services</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Condition:</strong> Service cost exceeds UGX 500,000</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Action:</strong> Flag for Review</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Message:</strong> "High-cost service requires manual review."</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 20</p>
                                    </div>

                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Rule 3: Flag Cosmetic Procedures</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Condition:</strong> Description contains keywords: "cosmetic", "plastic surgery", "aesthetic"</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Action:</strong> Flag for Review</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Message:</strong> "Cosmetic procedure requires review."</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 30</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pre-Authorization Triggers Explanation -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                Pre-Authorization Triggers
                            </h3>
                            <p class="text-sm text-slate-700 mb-4">
                                <strong>Pre-Authorization Triggers</strong> automatically create pre-authorization requests when certain conditions are met. This is useful for expensive procedures that need approval before the service is provided.
                            </p>
                            
                            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-slate-800 mb-2">When does it run?</h4>
                                <p class="text-sm text-slate-700 mb-3">When an invoice/service is created that matches your trigger conditions.</p>
                                
                                <h4 class="font-semibold text-slate-800 mb-2 mt-4">What happens?</h4>
                                <ol class="text-sm text-slate-700 space-y-2 list-decimal list-inside">
                                    <li>System checks if the service matches any trigger (cost, keywords, service category)</li>
                                    <li>If matched and "Auto-create" is enabled, a pre-authorization request is automatically created</li>
                                    <li>If amount is below "Auto-approval limit", it's automatically approved with an Approval ID</li>
                                    <li>If above the limit, it requires manual approval</li>
                                    <li>The Approval ID is automatically added to the invoice once approved</li>
                                </ol>
                            </div>

                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-green-900 mb-3">Example Triggers for Whysemedical (BPXX4Q9):</h4>
                                
                                <div class="space-y-4">
                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Trigger 1: High-Cost Services</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Type:</strong> Cost Threshold</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Condition:</strong> Service cost exceeds UGX 1,000,000</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-create:</strong> Yes</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-approve if below:</strong> UGX 500,000 (if amount is between 500k-1M, auto-approve)</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Require manual approval:</strong> Yes (for amounts above 1M)</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 10</p>
                                    </div>

                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Trigger 2: Surgery Keywords</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Type:</strong> Keyword Match</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Keywords:</strong> "surgery", "operation", "procedure", "surgical"</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-create:</strong> Yes</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-approve if below:</strong> UGX 300,000</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Require manual approval:</strong> Yes</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 20</p>
                                    </div>

                                    <div class="border-l-4 border-green-500 pl-4">
                                        <h5 class="font-semibold text-green-900 mb-1">Trigger 3: Special Procedures</h5>
                                        <p class="text-sm text-green-800 mb-2"><strong>Type:</strong> Service Category (e.g., "Surgery", "Specialist Consultation")</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Condition:</strong> Service category is "Surgery"</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-create:</strong> Yes</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Auto-approve if below:</strong> UGX 200,000</p>
                                        <p class="text-sm text-green-800 mb-2"><strong>Require manual approval:</strong> Yes</p>
                                        <p class="text-sm text-green-800"><strong>Priority:</strong> 30</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-World Scenario -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 mb-3">Real-World Scenario Example:</h4>
                            <div class="text-sm text-blue-800 space-y-3">
                                <p><strong>Scenario:</strong> A client visits a hospital and needs a surgery costing UGX 1,500,000.</p>
                                
                                <div class="bg-white rounded p-3 border border-blue-200">
                                    <p class="font-semibold mb-2">Step 1: Invoice Created</p>
                                    <p>Hospital creates invoice: "Surgical Procedure - Appendectomy" for UGX 1,500,000</p>
                                </div>

                                <div class="bg-white rounded p-3 border border-blue-200">
                                    <p class="font-semibold mb-2">Step 2: Coverage Decision Matrix Checks</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Rule 1 (OPD): Doesn't match ✓</li>
                                        <li>Rule 2 (High-cost): Matches! → Flags for review</li>
                                    </ul>
                                </div>

                                <div class="bg-white rounded p-3 border border-blue-200">
                                    <p class="font-semibold mb-2">Step 3: Pre-Authorization Trigger Checks</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Trigger 1 (Cost > 1M): Matches! → Creates pre-authorization</li>
                                        <li>Trigger 2 (Surgery keyword): Matches! → Also creates pre-authorization</li>
                                        <li>Since amount (1.5M) > auto-approval limit (500k), requires manual approval</li>
                                    </ul>
                                </div>

                                <div class="bg-white rounded p-3 border border-blue-200">
                                    <p class="font-semibold mb-2">Step 4: Manual Review & Approval</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Insurance company reviews the pre-authorization</li>
                                        <li>Approves for UGX 1,500,000</li>
                                        <li>System generates Approval ID: <code class="bg-blue-100 px-1 rounded">APP-20260208-ABC12345</code></li>
                                        <li>Approval ID is automatically added to the invoice</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Reference -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Quick Reference</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-slate-700 mb-2">Decision Matrix Actions:</h4>
                                <ul class="text-sm text-slate-600 space-y-1">
                                    <li>• <strong>Auto Reject:</strong> Immediately rejects the service</li>
                                    <li>• <strong>Flag for Review:</strong> Requires manual review before approval</li>
                                    <li>• <strong>Require Pre-Authorization:</strong> Creates a pre-auth request</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-700 mb-2">Priority System:</h4>
                                <ul class="text-sm text-slate-600 space-y-1">
                                    <li>• Lower number = Higher priority (checked first)</li>
                                    <li>• Example: Priority 10 is checked before Priority 20</li>
                                    <li>• First matching rule wins (stops checking others)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching functionality
    function switchTab(tabName) {
        // Hide all tab contents
        const allContents = document.querySelectorAll('.tab-content');
        allContents.forEach(content => {
            content.style.display = 'none';
        });
        
        // Remove active class from all tabs
        const allTabs = document.querySelectorAll('[id^="tab-"]');
        allTabs.forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-slate-500');
        });
        
        // Show selected tab content
        const selectedContent = document.getElementById('content-' + tabName);
        if (selectedContent) {
            selectedContent.style.display = 'block';
        }
        
        // Add active class to selected tab
        const selectedTab = document.getElementById('tab-' + tabName);
        if (selectedTab) {
            selectedTab.classList.remove('border-transparent', 'text-slate-500');
            selectedTab.classList.add('border-blue-500', 'text-blue-600');
        }
    }
    
    // Initialize first tab on page load
    document.addEventListener('DOMContentLoaded', function() {
        switchTab('policy-number');
        
        // Handle Physical ID requirement toggle
        const requirePhysicalIdCheckbox = document.getElementById('require_physical_id');
        if (requirePhysicalIdCheckbox) {
            function updatePhysicalIdSteps() {
                const isRequired = requirePhysicalIdCheckbox.checked;
                
                // Update all method step displays
                for (let i = 1; i <= 4; i++) {
                    const physicalIdStep = document.getElementById(`method${i}-step1-physical-id`);
                    const stepNum = document.getElementById(`method${i}-step-num`);
                    const physicalIdNote = document.getElementById(`method${i}-physical-id-note`);
                    
                    if (physicalIdStep) {
                        physicalIdStep.style.display = isRequired ? 'block' : 'none';
                    }
                    
                    if (stepNum) {
                        stepNum.textContent = isRequired ? '2' : '1';
                    }
                    
                    if (physicalIdNote) {
                        physicalIdNote.textContent = isRequired 
                            ? 'Physical National ID is also required if enabled above.' 
                            : 'Physical National ID is optional and can be enabled above.';
                    }
                }
            }
            
            requirePhysicalIdCheckbox.addEventListener('change', updatePhysicalIdSteps);
            updatePhysicalIdSteps(); // Initialize on page load
        }
        
        // Update preview on input change
        const formatInput = document.getElementById('policy_number_format');
        const randomLengthInput = document.getElementById('policy_number_random_length');
        const randomTypeInput = document.getElementById('policy_number_random_type');
        const companyCodeLengthInput = document.getElementById('policy_number_company_code_length');
        const previewElement = document.getElementById('preview-policy-number');
        
        if (formatInput && previewElement) {
            const companyCode = '{{ strtoupper(substr($insuranceCompany->code ?? "COMP", 0, 3)) }}';
            
            function updatePreview() {
                const format = formatInput.value || '{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}';
                const randomLength = parseInt(randomLengthInput.value) || 6;
                const randomType = randomTypeInput.value || 'alphanumeric';
                const codeLength = parseInt(companyCodeLengthInput.value) || 3;
                
                // Generate random part
                let randomPart = '';
                let characters = '';
                if (randomType === 'numeric') {
                    characters = '0123456789';
                } else if (randomType === 'alphabetic') {
                    characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                } else {
                    characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                }
                
                for (let i = 0; i < randomLength; i++) {
                    randomPart += characters[Math.floor(Math.random() * characters.length)];
                }
                
                // Replace placeholders
                let preview = format;
                preview = preview.replace(/{COMPANY}/g, companyCode.substring(0, codeLength));
                preview = preview.replace(/{YEAR}/g, new Date().getFullYear());
                preview = preview.replace(/{YEAR2}/g, String(new Date().getFullYear()).substring(2));
                preview = preview.replace(/{MONTH}/g, String(new Date().getMonth() + 1).padStart(2, '0'));
                preview = preview.replace(/{DAY}/g, String(new Date().getDate()).padStart(2, '0'));
                preview = preview.replace(/{RANDOM}/g, randomPart);
                
                previewElement.textContent = preview;
            }
            
            formatInput.addEventListener('input', updatePreview);
            if (randomLengthInput) randomLengthInput.addEventListener('input', updatePreview);
            if (randomTypeInput) randomTypeInput.addEventListener('change', updatePreview);
            if (companyCodeLengthInput) companyCodeLengthInput.addEventListener('input', updatePreview);
        }
    });
</script>

            <!-- Authorization Settings Tab -->
            <div id="content-authorization" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Authorization Settings</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure automatic authorization, rejection, and manual review thresholds for pre-authorizations.
                    </p>

                    <form action="{{ route('settings.update-authorization') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Enable Auto-Authorization -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div>
                                <label class="text-sm font-medium text-slate-900">Enable Automatic Authorization</label>
                                <p class="text-xs text-slate-600 mt-1">Automatically approve or reject pre-authorizations based on amount thresholds</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_auto_authorization" value="1" 
                                    {{ old('enable_auto_authorization', $insuranceCompany->enable_auto_authorization ?? true) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Auto-Approve Maximum Amount -->
                        <div>
                            <label for="auto_approve_max_amount" class="block text-sm font-medium text-slate-700 mb-2">
                                Auto-Approve Maximum Amount (UGX)
                            </label>
                            <input 
                                type="number" 
                                name="auto_approve_max_amount" 
                                id="auto_approve_max_amount" 
                                value="{{ old('auto_approve_max_amount', $insuranceCompany->auto_approve_max_amount ?? '') }}"
                                step="0.01"
                                min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 500000"
                            >
                            <p class="text-xs text-slate-500 mt-2">
                                Pre-authorizations with amounts ≤ this value will be automatically approved. Leave empty to disable auto-approval.
                            </p>
                        </div>

                        <!-- Auto-Reject Minimum Amount -->
                        <div>
                            <label for="auto_reject_min_amount" class="block text-sm font-medium text-slate-700 mb-2">
                                Auto-Reject Minimum Amount (UGX)
                            </label>
                            <input 
                                type="number" 
                                name="auto_reject_min_amount" 
                                id="auto_reject_min_amount" 
                                value="{{ old('auto_reject_min_amount', $insuranceCompany->auto_reject_min_amount ?? '') }}"
                                step="0.01"
                                min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 5000000"
                            >
                            <p class="text-xs text-slate-500 mt-2">
                                Pre-authorizations with amounts ≥ this value will be automatically rejected. Leave empty to disable auto-rejection.
                            </p>
                        </div>

                        <!-- Require Manual Review Above Amount -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div>
                                <label class="text-sm font-medium text-slate-900">Require Manual Review Above Threshold</label>
                                <p class="text-xs text-slate-600 mt-1">Flag pre-authorizations above a certain amount for manual review</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="require_manual_review_above_amount" value="1" 
                                    {{ old('require_manual_review_above_amount', $insuranceCompany->require_manual_review_above_amount ?? true) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Manual Review Threshold Amount -->
                        <div>
                            <label for="manual_review_threshold_amount" class="block text-sm font-medium text-slate-700 mb-2">
                                Manual Review Threshold Amount (UGX)
                            </label>
                            <input 
                                type="number" 
                                name="manual_review_threshold_amount" 
                                id="manual_review_threshold_amount" 
                                value="{{ old('manual_review_threshold_amount', $insuranceCompany->manual_review_threshold_amount ?? '') }}"
                                step="0.01"
                                min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 1000000"
                            >
                            <p class="text-xs text-slate-500 mt-2">
                                Pre-authorizations with amounts > this value will be flagged for manual review. Leave empty to disable.
                            </p>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">How Authorization Works:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>If amount ≤ Auto-Approve Max: <strong>Automatically Approved</strong></li>
                                        <li>If amount ≥ Auto-Reject Min: <strong>Automatically Rejected</strong></li>
                                        <li>If amount > Manual Review Threshold: <strong>Flagged for Manual Review</strong></li>
                                        <li>Otherwise: <strong>Flagged for Manual Review</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                Save Authorization Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
@endsection
