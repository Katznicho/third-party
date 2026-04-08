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
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Policy Numbers
                </button>
                <button 
                    onclick="switchTab('payment')"
                    id="tab-payment"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Payment
                </button>
                <button 
                    onclick="switchTab('account-number')"
                    id="tab-account-number"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Account Numbers
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
                <button 
                    onclick="switchTab('visit-authorization')"
                    id="tab-visit-authorization"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
                >
                    Visit Authorization
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
                        <input type="hidden" name="current_tab" id="current_tab_policy-number" value="policy-number">

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

                        <!-- Payment Responsibility Collection Setting -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                            <h3 class="text-sm font-semibold text-yellow-900 mb-3">Payment Responsibility Collection</h3>
                            <p class="text-xs text-yellow-700 mb-4">Configure when clients should pay their deductible, co-pay, and co-insurance amounts.</p>
                            
                            <div>
                                <label for="payment_responsibility_collection" class="block text-sm font-medium text-slate-700 mb-2">
                                    Collection Timing
                                </label>
                                <select 
                                    name="payment_responsibility_collection" 
                                    id="payment_responsibility_collection" 
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                    <option value="immediate" {{ old('payment_responsibility_collection', $insuranceCompany->payment_responsibility_collection ?? 'immediate') === 'immediate' ? 'selected' : '' }}>
                                        Immediate - Collect at time of service
                                    </option>
                                    <option value="later" {{ old('payment_responsibility_collection', $insuranceCompany->payment_responsibility_collection ?? 'immediate') === 'later' ? 'selected' : '' }}>
                                        Later - Collect after service is provided
                                    </option>
                                </select>
                                <p class="text-xs text-slate-500 mt-2">
                                    <strong>Immediate:</strong> Clients must pay deductible, co-pay, and co-insurance before or at the time of service.<br>
                                    <strong>Later:</strong> Clients can receive services first and pay their portion later (e.g., on invoice).
                                </p>
                            </div>
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

                <!-- Open Enrollment Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mt-6">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 mb-1">Open Enrollment</h2>
                            <p class="text-sm text-slate-600">
                                When enabled, clients are accepted based on criteria (age, sex) without a pre-registered policy.
                                A single <strong>generic policy</strong> is created and shared by all qualifying walk-in clients.
                            </p>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            @if($insuranceCompany->open_enrollment_enabled)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-2 h-2 mr-1.5 fill-green-600" viewBox="0 0 6 6" aria-hidden="true">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    Enabled
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-800">
                                    <svg class="w-2 h-2 mr-1.5 fill-slate-600" viewBox="0 0 6 6" aria-hidden="true">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    Not Enabled
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($insuranceCompany->open_enrollment_enabled)
                        <form action="{{ route('settings.update-open-enrollment') }}" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-sm text-blue-700">
                                        <strong>Generic Policy:</strong> A generic policy (<code class="text-xs bg-blue-100 px-1.5 py-0.5 rounded">GENERIC-{{ strtoupper($insuranceCompany->code) }}</code>) has been created and is used for all open enrollment clients.
                                    </p>
                                </div>
                            </div>

                            <!-- Criteria (shown when enabled) -->
                            <div class="space-y-5">

                            <!-- Age range -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Age Criteria <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Leave blank to accept clients of any age.</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="open_enrollment_min_age" class="block text-sm font-medium text-slate-700 mb-1">Minimum age</label>
                                        <input
                                            type="number"
                                            name="open_enrollment_min_age"
                                            id="open_enrollment_min_age"
                                            value="{{ old('open_enrollment_min_age', $insuranceCompany->open_enrollment_min_age) }}"
                                            min="0" max="150"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g. 18"
                                        >
                                    </div>
                                    <div>
                                        <label for="open_enrollment_max_age" class="block text-sm font-medium text-slate-700 mb-1">Maximum age</label>
                                        <input
                                            type="number"
                                            name="open_enrollment_max_age"
                                            id="open_enrollment_max_age"
                                            value="{{ old('open_enrollment_max_age', $insuranceCompany->open_enrollment_max_age) }}"
                                            min="0" max="150"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g. 65"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Gender criteria -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Gender Criteria <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Select which genders qualify. Leave all unchecked to accept any gender.</p>
                                @php
                                    $savedGenders = old('open_enrollment_genders', $insuranceCompany->open_enrollment_genders ?? []);
                                    $savedGenders = is_array($savedGenders) ? $savedGenders : [];
                                @endphp
                                <div class="flex gap-6 items-center">
                                    @foreach(['Male' => 'Male', 'Female' => 'Female'] as $value => $label)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="open_enrollment_genders[]"
                                                value="{{ $value }}"
                                                {{ in_array($value, $savedGenders) ? 'checked' : '' }}
                                                class="gender-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                onchange="syncBothGender()"
                                            >
                                            <span class="text-sm text-slate-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                    <span class="text-slate-300 select-none">|</span>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            id="gender_both"
                                            {{ count($savedGenders) === 2 ? 'checked' : '' }}
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            onchange="toggleBothGenders(this.checked)"
                                        >
                                        <span class="text-sm text-slate-700 font-medium">Both</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Service Categories -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Allowed Service Categories <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Restrict open enrollment to specific service types. Leave all unchecked to allow any category.</p>
                                @php
                                    $savedCategories = old('open_enrollment_service_categories', $insuranceCompany->open_enrollment_service_categories ?? []);
                                    $savedCategories = is_array($savedCategories) ? $savedCategories : [];
                                    $allCategories = \App\Models\ServiceCategory::orderBy('name')->get(['slug', 'name']);
                                @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach($allCategories as $cat)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="open_enrollment_service_categories[]"
                                                value="{{ $cat->slug }}"
                                                {{ in_array($cat->slug, $savedCategories) ? 'checked' : '' }}
                                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-slate-700">{{ $cat->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Enrollment Window -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Enrollment Window <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Limit open enrollment to a date range, e.g. a seasonal campaign. Leave blank to allow anytime.</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="open_enrollment_start_date" class="block text-sm font-medium text-slate-700 mb-1">Start date</label>
                                        <input
                                            type="date"
                                            name="open_enrollment_start_date"
                                            id="open_enrollment_start_date"
                                            value="{{ old('open_enrollment_start_date', $insuranceCompany->open_enrollment_start_date?->toDateString()) }}"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                    </div>
                                    <div>
                                        <label for="open_enrollment_end_date" class="block text-sm font-medium text-slate-700 mb-1">End date</label>
                                        <input
                                            type="date"
                                            name="open_enrollment_end_date"
                                            id="open_enrollment_end_date"
                                            value="{{ old('open_enrollment_end_date', $insuranceCompany->open_enrollment_end_date?->toDateString()) }}"
                                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Max Invoice Amount -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Maximum Invoice Amount <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Cap the total amount that can be authorized per invoice under the generic policy. Leave blank for no cap.</p>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">{{ strtoupper($insuranceCompany->currency_code ?? 'UGX') }}</span>
                                    <input
                                        type="number"
                                        name="open_enrollment_max_invoice_amount"
                                        id="open_enrollment_max_invoice_amount"
                                        value="{{ old('open_enrollment_max_invoice_amount', $insuranceCompany->open_enrollment_max_invoice_amount) }}"
                                        min="0" step="1"
                                        class="w-full pl-14 pr-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="e.g. 500000"
                                    >
                                </div>
                            </div>

                            <!-- Nationality -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Nationality <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Comma-separated list of accepted nationalities, e.g. <em>Ugandan, Kenyan</em>. Leave blank to accept any nationality.</p>
                                @php
                                    $savedNationalities = old('open_enrollment_nationalities_text',
                                        implode(', ', $insuranceCompany->open_enrollment_nationalities ?? []));
                                @endphp
                                <input
                                    type="hidden"
                                    name="open_enrollment_nationalities_text"
                                    id="open_enrollment_nationalities_text_hidden"
                                    value="{{ $savedNationalities }}"
                                >
                                <input
                                    type="text"
                                    id="open_enrollment_nationalities_input"
                                    value="{{ $savedNationalities }}"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g. Ugandan, Kenyan"
                                    oninput="syncNationalities(this.value)"
                                >
                                {{-- Hidden array inputs built by JS --}}
                                <div id="open_enrollment_nationalities_inputs"></div>
                            </div>

                            <!-- Marital Status -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Marital Status <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Select which marital statuses qualify. Leave all unchecked to accept any.</p>
                                @php
                                    $savedMarital = old('open_enrollment_marital_statuses', $insuranceCompany->open_enrollment_marital_statuses ?? []);
                                    $savedMarital = is_array($savedMarital) ? $savedMarital : [];
                                @endphp
                                <div class="flex flex-wrap gap-4">
                                    @foreach(['Single', 'Married', 'Divorced', 'Widowed'] as $status)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="open_enrollment_marital_statuses[]"
                                                value="{{ $status }}"
                                                {{ in_array($status, $savedMarital) ? 'checked' : '' }}
                                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-slate-700">{{ $status }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Client Type -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">Client Type <span class="text-slate-400 font-normal">(optional)</span></h3>
                                <p class="text-xs text-slate-500 mb-3">Restrict to principal members, dependents, or both. Leave all unchecked to accept any type.</p>
                                @php
                                    $savedTypes = old('open_enrollment_client_types', $insuranceCompany->open_enrollment_client_types ?? []);
                                    $savedTypes = is_array($savedTypes) ? $savedTypes : [];
                                @endphp
                                <div class="flex gap-6">
                                    @foreach(['principal' => 'Principal member', 'dependent' => 'Dependent'] as $value => $label)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="open_enrollment_client_types[]"
                                                value="{{ $value }}"
                                                {{ in_array($value, $savedTypes) ? 'checked' : '' }}
                                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-slate-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Generic policy info -->
                            @if($insuranceCompany->generic_policy_id && $insuranceCompany->genericPolicy)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-green-800">Generic policy active</p>
                                    <p class="text-xs text-green-700 mt-0.5">
                                        Policy number: <code class="bg-white px-1 rounded font-mono">{{ $insuranceCompany->genericPolicy->policy_number }}</code>
                                        &mdash; All qualifying walk-in clients will be authorized under this policy.
                                    </p>
                                </div>
                            </div>
                            @else
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <p class="text-xs text-amber-800">A generic policy will be created automatically when you save these settings for the first time.</p>
                            </div>
                            @endif
                            </div>

                            <div class="flex justify-end pt-4 border-t border-slate-200">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                    Save Open Enrollment Settings
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-slate-700">
                                    <strong>Open enrollment is not enabled</strong> for your vendor. This setting is configured at vendor creation and cannot be changed later. 
                                    If you need to switch modes, contact your administrator.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Tab -->
            <div id="content-payment" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Payment Settings</h2>
                    <p class="text-sm text-slate-600 mb-6">Configure allowed payment methods. Clients and vendors will only see these options.</p>

                    <form action="{{ route('settings.update-payment') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_tab" value="payment">

                        <!-- Country & Currency -->
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">Location & Currency</h3>
                            <p class="text-xs text-slate-600 mb-4">Set insurer country and billing currency. Default currency is UGX.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="country_name" class="block text-sm font-medium text-slate-700 mb-2">Country</label>
                                    <input
                                        type="text"
                                        name="country_name"
                                        id="country_name"
                                        value="{{ old('country_name', $insuranceCompany->country_name) }}"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="e.g. Uganda"
                                    >
                                </div>
                                <div>
                                    <label for="currency_code" class="block text-sm font-medium text-slate-700 mb-2">Currency Code</label>
                                    <input
                                        type="text"
                                        name="currency_code"
                                        id="currency_code"
                                        value="{{ old('currency_code', strtoupper($insuranceCompany->currency_code ?? 'UGX')) }}"
                                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="UGX"
                                    >
                                    <p class="text-xs text-slate-500 mt-1">If left blank, system uses UGX.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">Allowed Payment Methods</h3>
                            <p class="text-xs text-slate-600 mb-4">Select which payment methods are accepted. Clients and vendors will only see these options.</p>
                            @php
                                $savedMethods = old('payment_methods', $insuranceCompany->payment_methods ?? []);
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 border border-slate-200 rounded-lg p-4 bg-white">
                                @foreach(\App\Models\InsuranceCompany::getPaymentMethodOptions() as $value => $label)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="payment_methods[]" 
                                            value="{{ $value }}"
                                            {{ in_array($value, is_array($savedMethods) ? $savedMethods : []) ? 'checked' : '' }}
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-slate-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-200">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                Save Payment Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Number Generation Tab -->
            <div id="content-account-number" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Account Number Generation</h2>
                    <p class="text-sm text-slate-600 mb-6">Configure how account numbers are generated for client accounts. Account numbers must be exactly 12 digits.</p>

                    <form action="{{ route('settings.update-account-number') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_tab" id="current_tab_account-number" value="account-number">

                        <!-- Account Number Format -->
                        <div>
                            <label for="account_number_format" class="block text-sm font-medium text-slate-700 mb-2">
                                Account Number Format
                            </label>
                            <input 
                                type="text" 
                                name="account_number_format" 
                                id="account_number_format" 
                                value="{{ old('account_number_format', $insuranceCompany->account_number_format ?? '{COMPANY}{YEAR}{RANDOM}') }}"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="{COMPANY}{YEAR}{RANDOM}"
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
                                <strong>Note:</strong> The final account number will be exactly 12 digits. Non-numeric characters will be removed, and the number will be padded or truncated to 12 digits.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Random Length -->
                            <div>
                                <label for="account_number_random_length" class="block text-sm font-medium text-slate-700 mb-2">
                                    Random Part Length
                                </label>
                                <input 
                                    type="number" 
                                    name="account_number_random_length" 
                                    id="account_number_random_length" 
                                    value="{{ old('account_number_random_length', $insuranceCompany->account_number_random_length ?? 6) }}"
                                    min="1"
                                    max="12"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                <p class="text-xs text-slate-500 mt-1">Base length for random part (will be adjusted to ensure 12 digits total)</p>
                            </div>

                            <!-- Random Type -->
                            <div>
                                <label for="account_number_random_type" class="block text-sm font-medium text-slate-700 mb-2">
                                    Random Part Type
                                </label>
                                <select 
                                    name="account_number_random_type" 
                                    id="account_number_random_type" 
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                    <option value="numeric" {{ old('account_number_random_type', $insuranceCompany->account_number_random_type ?? 'numeric') === 'numeric' ? 'selected' : '' }}>Numeric (0-9)</option>
                                    <option value="alphanumeric" {{ old('account_number_random_type', $insuranceCompany->account_number_random_type ?? 'numeric') === 'alphanumeric' ? 'selected' : '' }}>Alphanumeric (A-Z, 0-9)</option>
                                    <option value="alphabetic" {{ old('account_number_random_type', $insuranceCompany->account_number_random_type ?? 'numeric') === 'alphabetic' ? 'selected' : '' }}>Alphabetic (A-Z)</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Type of characters for random part</p>
                            </div>

                            <!-- Company Code Length -->
                            <div>
                                <label for="account_number_company_code_length" class="block text-sm font-medium text-slate-700 mb-2">
                                    Company Code Length
                                </label>
                                <input 
                                    type="number" 
                                    name="account_number_company_code_length" 
                                    id="account_number_company_code_length" 
                                    value="{{ old('account_number_company_code_length', $insuranceCompany->account_number_company_code_length ?? 3) }}"
                                    min="1"
                                    max="8"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                <p class="text-xs text-slate-500 mt-1">Characters from company code to use</p>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Preview</label>
                            <div class="flex items-center gap-3">
                                <code class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono" id="preview-account-number">
                                    {{ strtoupper(substr($insuranceCompany->code ?? 'ACC', 0, 3)) }}{{ now()->format('Y') }}123456
                                </code>
                                <span class="text-xs text-slate-500">(12 digits)</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">This is a preview. Actual account numbers will be generated based on your settings.</p>
                        </div>

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
                        <input type="hidden" name="current_tab" id="current_tab_deductible" value="deductible">

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
                        <input type="hidden" name="current_tab" id="current_tab_client-fields" value="client-fields">

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
                        <input type="hidden" name="current_tab" id="current_tab_verification" value="verification">

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

                            <!-- Test/Demo Section -->
                            <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                                <h4 class="text-sm font-semibold text-slate-800 mb-3">🧪 Test Tolerance Settings</h4>
                                <p class="text-xs text-slate-600 mb-4">Test how your tolerance settings work with sample names and dates.</p>
                                
                                <div class="space-y-4">
                                    <!-- Name Similarity Test -->
                                    <div class="bg-white p-4 rounded-lg border border-slate-200">
                                        <h5 class="text-sm font-medium text-slate-700 mb-3">Name Similarity Test</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                            <div>
                                                <label class="block text-xs text-slate-600 mb-1">Name 1 (Registered)</label>
                                                <input 
                                                    type="text" 
                                                    id="test_name1" 
                                                    placeholder="e.g., John Doe"
                                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-xs text-slate-600 mb-1">Name 2 (Provided)</label>
                                                <div class="flex gap-2">
                                                    <input 
                                                        type="text" 
                                                        id="test_name2" 
                                                        placeholder="e.g., Jon Doe"
                                                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    >
                                                    <button 
                                                        type="button" 
                                                        onclick="generateRandomNames()"
                                                        class="px-3 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700"
                                                        title="Generate random test names"
                                                    >
                                                        🎲
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button 
                                            type="button" 
                                            onclick="testNameSimilarity()"
                                            class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700"
                                        >
                                            Test Name Similarity
                                        </button>
                                        <div id="name_test_result" class="mt-3 p-3 rounded-lg hidden"></div>
                                    </div>

                                    <!-- Date of Birth Tolerance Test -->
                                    <div class="bg-white p-4 rounded-lg border border-slate-200">
                                        <h5 class="text-sm font-medium text-slate-700 mb-3">Date of Birth Tolerance Test</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                            <div>
                                                <label class="block text-xs text-slate-600 mb-1">Date 1 (Registered)</label>
                                                <input 
                                                    type="date" 
                                                    id="test_dob1" 
                                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-xs text-slate-600 mb-1">Date 2 (Provided)</label>
                                                <div class="flex gap-2">
                                                    <input 
                                                        type="date" 
                                                        id="test_dob2" 
                                                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    >
                                                    <button 
                                                        type="button" 
                                                        onclick="generateRandomDates()"
                                                        class="px-3 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700"
                                                        title="Generate random test dates"
                                                    >
                                                        🎲
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button 
                                            type="button" 
                                            onclick="testDobTolerance()"
                                            class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700"
                                        >
                                            Test DOB Tolerance
                                        </button>
                                        <div id="dob_test_result" class="mt-3 p-3 rounded-lg hidden"></div>
                                    </div>
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
        
        // Update all hidden tab inputs to reflect current tab
        const allTabInputs = document.querySelectorAll('input[name="current_tab"]');
        allTabInputs.forEach(input => {
            input.value = tabName;
        });
        
        // Update URL hash without reloading
        if (history.pushState) {
            history.pushState(null, null, '#' + tabName);
        }
    }
    
    // Initialize first tab on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check for tab in URL hash or query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab') || window.location.hash.substring(1);
        const initialTab = tabFromUrl || 'policy-number';
        
        switchTab(initialTab);
        
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
        
        // Account number preview
        const accountFormatInput = document.getElementById('account_number_format');
        const accountRandomLengthInput = document.getElementById('account_number_random_length');
        const accountRandomTypeInput = document.getElementById('account_number_random_type');
        const accountCompanyCodeLengthInput = document.getElementById('account_number_company_code_length');
        const accountPreviewElement = document.getElementById('preview-account-number');
        
        if (accountFormatInput && accountPreviewElement) {
            const companyCode = '{{ strtoupper(substr($insuranceCompany->code ?? "ACC", 0, 3)) }}';
            
            function updateAccountPreview() {
                const format = accountFormatInput.value || '{COMPANY}{YEAR}{RANDOM}';
                const randomLength = parseInt(accountRandomLengthInput.value) || 6;
                const randomType = accountRandomTypeInput.value || 'numeric';
                const codeLength = parseInt(accountCompanyCodeLengthInput.value) || 3;
                
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
                
                // Remove non-numeric and ensure 12 digits
                preview = preview.replace(/[^0-9]/g, '');
                if (preview.length < 12) {
                    preview = preview.padEnd(12, '0');
                } else if (preview.length > 12) {
                    preview = preview.substring(0, 12);
                }
                
                accountPreviewElement.textContent = preview;
            }
            
            accountFormatInput.addEventListener('input', updateAccountPreview);
            if (accountRandomLengthInput) accountRandomLengthInput.addEventListener('input', updateAccountPreview);
            if (accountRandomTypeInput) accountRandomTypeInput.addEventListener('change', updateAccountPreview);
            if (accountCompanyCodeLengthInput) accountCompanyCodeLengthInput.addEventListener('input', updateAccountPreview);
        }
    });

    // Calculate name similarity using Levenshtein distance (same algorithm as backend)
    function calculateNameSimilarity(name1, name2) {
        name1 = name1.toLowerCase().trim();
        name2 = name2.toLowerCase().trim();
        
        if (name1 === name2) {
            return 100;
        }

        const maxLength = Math.max(name1.length, name2.length);
        if (maxLength === 0) {
            return 0;
        }

        // Levenshtein distance calculation
        const matrix = [];
        for (let i = 0; i <= name2.length; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= name1.length; j++) {
            matrix[0][j] = j;
        }

        for (let i = 1; i <= name2.length; i++) {
            for (let j = 1; j <= name1.length; j++) {
                if (name2.charAt(i - 1) === name1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }

        const distance = matrix[name2.length][name1.length];
        const similarity = (1 - (distance / maxLength)) * 100;
        
        return Math.round(similarity);
    }

    // Test name similarity
    function testNameSimilarity() {
        const name1 = document.getElementById('test_name1').value.trim();
        const name2 = document.getElementById('test_name2').value.trim();
        const threshold = parseInt(document.getElementById('name_similarity_threshold').value) || 80;
        const resultDiv = document.getElementById('name_test_result');

        if (!name1 || !name2) {
            resultDiv.className = 'mt-3 p-3 rounded-lg bg-yellow-50 border border-yellow-200';
            resultDiv.innerHTML = '<p class="text-sm text-yellow-800">Please enter both names to test.</p>';
            resultDiv.classList.remove('hidden');
            return;
        }

        const similarity = calculateNameSimilarity(name1, name2);
        const matches = similarity >= threshold;

        resultDiv.className = matches 
            ? 'mt-3 p-3 rounded-lg bg-green-50 border border-green-200' 
            : 'mt-3 p-3 rounded-lg bg-red-50 border border-red-200';
        
        resultDiv.innerHTML = `
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium ${matches ? 'text-green-800' : 'text-red-800'}">
                        Similarity: <strong>${similarity}%</strong>
                    </span>
                    <span class="px-2 py-1 text-xs font-semibold rounded ${matches ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">
                        ${matches ? '✓ MATCH' : '✗ NO MATCH'}
                    </span>
                </div>
                <div class="text-xs ${matches ? 'text-green-700' : 'text-red-700'}">
                    <p><strong>Threshold:</strong> ${threshold}%</p>
                    <p><strong>Name 1 (Registered):</strong> "${name1}"</p>
                    <p><strong>Name 2 (Provided):</strong> "${name2}"</p>
                    ${!matches ? `<p class="mt-1"><strong>Reason:</strong> Similarity (${similarity}%) is below the threshold (${threshold}%)</p>` : ''}
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    }

    // Test DOB tolerance
    function testDobTolerance() {
        const dob1 = document.getElementById('test_dob1').value;
        const dob2 = document.getElementById('test_dob2').value;
        const toleranceDays = parseInt(document.getElementById('dob_tolerance_days').value) || 0;
        const resultDiv = document.getElementById('dob_test_result');

        if (!dob1 || !dob2) {
            resultDiv.className = 'mt-3 p-3 rounded-lg bg-yellow-50 border border-yellow-200';
            resultDiv.innerHTML = '<p class="text-sm text-yellow-800">Please enter both dates to test.</p>';
            resultDiv.classList.remove('hidden');
            return;
        }

        const date1 = new Date(dob1);
        const date2 = new Date(dob2);
        const daysDiff = Math.abs(Math.floor((date2 - date1) / (1000 * 60 * 60 * 24)));
        const matches = daysDiff <= toleranceDays;

        resultDiv.className = matches 
            ? 'mt-3 p-3 rounded-lg bg-green-50 border border-green-200' 
            : 'mt-3 p-3 rounded-lg bg-red-50 border border-red-200';
        
        resultDiv.innerHTML = `
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium ${matches ? 'text-green-800' : 'text-red-800'}">
                        Difference: <strong>${daysDiff} day${daysDiff !== 1 ? 's' : ''}</strong>
                    </span>
                    <span class="px-2 py-1 text-xs font-semibold rounded ${matches ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">
                        ${matches ? '✓ MATCH' : '✗ NO MATCH'}
                    </span>
                </div>
                <div class="text-xs ${matches ? 'text-green-700' : 'text-red-700'}">
                    <p><strong>Tolerance:</strong> ${toleranceDays} day${toleranceDays !== 1 ? 's' : ''}</p>
                    <p><strong>Date 1 (Registered):</strong> ${new Date(dob1).toLocaleDateString()}</p>
                    <p><strong>Date 2 (Provided):</strong> ${new Date(dob2).toLocaleDateString()}</p>
                    ${!matches ? `<p class="mt-1"><strong>Reason:</strong> Difference (${daysDiff} days) exceeds tolerance (${toleranceDays} days)</p>` : ''}
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    }

    // Generate random test names
    function generateRandomNames() {
        const firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Mary', 'Robert', 'Patricia', 'William', 'Jennifer', 'Richard', 'Linda', 'Joseph', 'Elizabeth'];
        const lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor'];
        
        const name1 = firstNames[Math.floor(Math.random() * firstNames.length)] + ' ' + lastNames[Math.floor(Math.random() * lastNames.length)];
        const name2 = firstNames[Math.floor(Math.random() * firstNames.length)] + ' ' + lastNames[Math.floor(Math.random() * lastNames.length)];
        
        // Sometimes make name2 similar to name1 for better testing
        if (Math.random() > 0.5) {
            // Make a variation of name1
            const parts = name1.split(' ');
            const variations = [
                parts[0].substring(0, 3) + parts[0].substring(3).replace(/./g, '') + ' ' + parts[1], // Remove some letters
                parts[0] + ' ' + parts[1].substring(0, 2) + parts[1].substring(2), // Slight variation
                parts[0].charAt(0) + parts[0].substring(1).replace(/[aeiou]/gi, '') + ' ' + parts[1], // Remove vowels
            ];
            document.getElementById('test_name1').value = name1;
            document.getElementById('test_name2').value = variations[Math.floor(Math.random() * variations.length)];
        } else {
            document.getElementById('test_name1').value = name1;
            document.getElementById('test_name2').value = name2;
        }
    }

    // Generate random test dates
    function generateRandomDates() {
        const today = new Date();
        const baseDate = new Date(today.getFullYear() - 30, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
        const toleranceDays = parseInt(document.getElementById('dob_tolerance_days').value) || 0;
        
        // Generate date1
        const date1 = new Date(baseDate);
        date1.setDate(date1.getDate() - Math.floor(Math.random() * 365));
        
        // Generate date2 - sometimes within tolerance, sometimes outside
        const date2 = new Date(date1);
        if (Math.random() > 0.5 && toleranceDays > 0) {
            // Within tolerance
            const offset = Math.floor(Math.random() * (toleranceDays * 2 + 1)) - toleranceDays;
            date2.setDate(date2.getDate() + offset);
        } else {
            // Outside tolerance (or no tolerance set)
            const offset = toleranceDays + Math.floor(Math.random() * 10) + 1;
            date2.setDate(date2.getDate() + (Math.random() > 0.5 ? offset : -offset));
        }
        
        document.getElementById('test_dob1').value = date1.toISOString().split('T')[0];
        document.getElementById('test_dob2').value = date2.toISOString().split('T')[0];
    }

    function updateApprovalLevelVisibility() {
        const levels = parseInt(document.getElementById('invoice_authorization_levels')?.value || '1');
        for (let i = 1; i <= 3; i++) {
            const el = document.getElementById('approval-level-' + i);
            if (el) {
                el.classList.toggle('hidden', i > levels);
            }
        }
    }

    function filterApprovers(input, level) {
        const term = input.value.toLowerCase();
        document.querySelectorAll('.approver-row-' + level).forEach(function(row) {
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            row.style.display = (name.includes(term) || email.includes(term)) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateApprovalLevelVisibility();
    });

    function toggleBothGenders(checked) {
        document.querySelectorAll('.gender-checkbox').forEach(cb => cb.checked = checked);
    }

    function syncBothGender() {
        const boxes = document.querySelectorAll('.gender-checkbox');
        const allChecked = Array.from(boxes).every(cb => cb.checked);
        const bothEl = document.getElementById('gender_both');
        if (bothEl) bothEl.checked = allChecked;
    }

    function toggleOpenEnrollmentCriteria(enabled) {
        const criteria = document.getElementById('open-enrollment-criteria');
        if (criteria) {
            criteria.classList.toggle('hidden', !enabled);
        }
    }

    function syncNationalities(value) {
        const container = document.getElementById('open_enrollment_nationalities_inputs');
        if (!container) return;
        container.innerHTML = '';
        value.split(',').map(s => s.trim()).filter(Boolean).forEach(nat => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'open_enrollment_nationalities[]';
            inp.value = nat;
            container.appendChild(inp);
        });
    }

    // Initialise nationality hidden inputs on page load
    document.addEventListener('DOMContentLoaded', function () {
        const natInput = document.getElementById('open_enrollment_nationalities_input');
        if (natInput) syncNationalities(natInput.value);
    });
</script>

            <!-- Authorization Settings Tab -->
            <div id="content-authorization" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Authorization Settings</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure automatic authorization, rejection, manual review thresholds, and multi-level approval for pre-authorizations.
                    </p>

                    <form action="{{ route('settings.update-authorization') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_tab" id="current_tab_authorization" value="authorization">

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
                            <label for="auto_approve_max_amount" class="block text-sm font-medium text-slate-700 mb-2">Auto-Approve Maximum Amount (UGX)</label>
                            <input type="number" name="auto_approve_max_amount" id="auto_approve_max_amount"
                                value="{{ old('auto_approve_max_amount', $insuranceCompany->auto_approve_max_amount ?? '') }}"
                                step="0.01" min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 500000">
                            <p class="text-xs text-slate-500 mt-2">Pre-authorizations with amounts &le; this value will be automatically approved. Leave empty to disable.</p>
                        </div>

                        <!-- Auto-Reject Minimum Amount -->
                        <div>
                            <label for="auto_reject_min_amount" class="block text-sm font-medium text-slate-700 mb-2">Auto-Reject Minimum Amount (UGX)</label>
                            <input type="number" name="auto_reject_min_amount" id="auto_reject_min_amount"
                                value="{{ old('auto_reject_min_amount', $insuranceCompany->auto_reject_min_amount ?? '') }}"
                                step="0.01" min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 5000000">
                            <p class="text-xs text-slate-500 mt-2">Pre-authorizations with amounts &ge; this value will be automatically rejected. Leave empty to disable.</p>
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
                            <label for="manual_review_threshold_amount" class="block text-sm font-medium text-slate-700 mb-2">Manual Review Threshold Amount (UGX)</label>
                            <input type="number" name="manual_review_threshold_amount" id="manual_review_threshold_amount"
                                value="{{ old('manual_review_threshold_amount', $insuranceCompany->manual_review_threshold_amount ?? '') }}"
                                step="0.01" min="0"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 1000000">
                            <p class="text-xs text-slate-500 mt-2">Pre-authorizations with amounts &gt; this value will be flagged for manual review. Leave empty to disable.</p>
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
                                        <li>If amount &le; Auto-Approve Max: <strong>Automatically Approved</strong></li>
                                        <li>If amount &ge; Auto-Reject Min: <strong>Automatically Rejected</strong></li>
                                        <li>If amount &gt; Manual Review Threshold: <strong>Flagged for Manual Review</strong></li>
                                        <li>Otherwise: <strong>Flagged for Manual Review</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-200">

                        <!-- Manual Approval Workflow -->
                        <div class="border border-blue-200 rounded-lg p-5 bg-blue-50/30">
                            <h3 class="text-base font-semibold text-slate-900 mb-2">Manual Approval Workflow</h3>
                            <p class="text-xs text-slate-600 mb-4">Set the number of approval levels and assign users to each level. Pre-authorizations flagged for manual review must be approved at each level in order.</p>

                            <div class="mb-4">
                                <label for="invoice_authorization_levels" class="block text-sm font-medium text-slate-700 mb-1">Number of approval levels</label>
                                <select name="invoice_authorization_levels" id="invoice_authorization_levels"
                                        onchange="updateApprovalLevelVisibility()"
                                        class="w-full max-w-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="1" {{ old('invoice_authorization_levels', $insuranceCompany->invoice_authorization_levels ?? 1) == 1 ? 'selected' : '' }}>1 Level</option>
                                    <option value="2" {{ old('invoice_authorization_levels', $insuranceCompany->invoice_authorization_levels ?? 1) == 2 ? 'selected' : '' }}>2 Levels</option>
                                    <option value="3" {{ old('invoice_authorization_levels', $insuranceCompany->invoice_authorization_levels ?? 1) == 3 ? 'selected' : '' }}>3 Levels</option>
                                </select>
                            </div>

                            @php
                                $levelLabels = [1 => 'Level 1 – First Reviewer', 2 => 'Level 2 – Second Reviewer', 3 => 'Level 3 – Final Approver'];
                                $levelBg = [1 => 'bg-green-50 border-green-200', 2 => 'bg-yellow-50 border-yellow-200', 3 => 'bg-blue-50 border-blue-200'];
                                $levelRing = [1 => 'green', 2 => 'yellow', 3 => 'blue'];
                                $currentLevels = (int) old('invoice_authorization_levels', $insuranceCompany->invoice_authorization_levels ?? 1);
                            @endphp

                            @foreach([1, 2, 3] as $lvl)
                                <div id="approval-level-{{ $lvl }}" class="approval-level-section mb-4 p-4 rounded-lg border {{ $levelBg[$lvl] }} {{ $lvl > $currentLevels ? 'hidden' : '' }}">
                                    <h5 class="text-sm font-semibold text-slate-900 mb-2">{{ $levelLabels[$lvl] }}</h5>
                                    <p class="text-xs text-slate-500 mb-2">Select users who can approve at this level.</p>
                                    <div class="mb-2">
                                        <input type="text" placeholder="Search by name or email…" oninput="filterApprovers(this, {{ $lvl }})"
                                               class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-{{ $levelRing[$lvl] }}-500">
                                    </div>
                                    <div id="approvers-list-{{ $lvl }}" class="space-y-1 max-h-40 overflow-y-auto">
                                        @foreach($users as $u)
                                            <label class="flex items-center space-x-2 approver-row-{{ $lvl }} cursor-pointer" data-name="{{ strtolower($u->name) }}" data-email="{{ strtolower($u->email) }}">
                                                <input type="checkbox"
                                                       name="approvers_level_{{ $lvl }}[]"
                                                       value="{{ $u->id }}"
                                                       {{ $insuranceCompany->preAuthorizationApprovers->where('level', $lvl)->where('user_id', $u->id)->count() > 0 ? 'checked' : '' }}
                                                       class="h-4 w-4 text-{{ $levelRing[$lvl] }}-600 focus:ring-{{ $levelRing[$lvl] }}-500 border-slate-300 rounded">
                                                <span class="text-sm text-slate-900">{{ $u->name }} <span class="text-slate-500 text-xs">({{ $u->email }})</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="mt-2 p-3 rounded-lg bg-slate-100 border border-slate-200">
                                <p class="text-xs text-slate-700"><strong>How it works:</strong> When a pre-authorization is flagged for manual review, it must be approved at Level 1 first. If you have 2 or 3 levels, it then moves to Level 2 (and Level 3) for additional approval before it is fully approved.</p>
                            </div>
                        </div>

                        <hr class="border-slate-200">

                        <!-- Authorization validity -->
                        <div>
                            <label for="authorization_valid_days" class="block text-sm font-medium text-slate-700 mb-2">Authorization validity period</label>
                            <p class="text-xs text-slate-500 mb-2">How long an authorization stays valid. Leave empty for no expiry.</p>
                            <div class="flex items-center gap-3">
                                <input type="number" name="authorization_valid_days" id="authorization_valid_days"
                                       min="1" max="365" placeholder="e.g., 30"
                                       value="{{ old('authorization_valid_days', $insuranceCompany->authorization_valid_days ?? '') }}"
                                       class="w-full max-w-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select name="authorization_valid_unit" id="authorization_valid_unit"
                                        class="px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @php
                                        $validUnit = old('authorization_valid_unit', $insuranceCompany->authorization_valid_unit ?? 'days');
                                    @endphp
                                    <option value="minutes" {{ $validUnit === 'minutes' ? 'selected' : '' }}>Minutes</option>
                                    <option value="hours" {{ $validUnit === 'hours' ? 'selected' : '' }}>Hours</option>
                                    <option value="days" {{ $validUnit === 'days' ? 'selected' : '' }}>Days</option>
                                </select>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Example: 30 + Days = 30 days, 4 + Hours = 4 hours.</p>
                        </div>

                        <!-- Re-authorize if edited -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div>
                                <label class="text-sm font-medium text-slate-900">Re-authorize if invoice edited</label>
                                <p class="text-xs text-slate-600 mt-1">If items or amounts change after authorization, require a new authorization</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="require_reauthorize_if_edited" value="0">
                                <input type="checkbox" name="require_reauthorize_if_edited" value="1"
                                    {{ old('require_reauthorize_if_edited', $insuranceCompany->require_reauthorize_if_edited ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Stop credit after grace period -->
                        <div class="p-4 bg-red-50 rounded-lg mt-4">
                            <label class="text-sm font-medium text-red-900 block">
                                Stop credit when grace period expires
                            </label>
                            <p class="text-xs text-red-700 mt-1">
                                Once the premium grace period ends, choose what should happen to new invoices for this insurer's policies.
                            </p>

                            <div class="mt-3 max-w-md">
                                <label for="stop_credit_after_grace_behavior" class="block text-xs font-medium text-red-900 mb-1">
                                    Behavior when grace period has expired
                                </label>
                                @php
                                    $behavior = old('stop_credit_after_grace_behavior', $insuranceCompany->stop_credit_after_grace_behavior ?? 'client_pays_full');
                                @endphp
                                <select
                                    name="stop_credit_after_grace_behavior"
                                    id="stop_credit_after_grace_behavior"
                                    class="w-full px-3 py-2 border border-red-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 bg-white"
                                >
                                    <option value="client_pays_full" {{ $behavior === 'client_pays_full' ? 'selected' : '' }}>
                                        Client pays full amount (no credit)
                                    </option>
                                    <option value="manual_review" {{ $behavior === 'manual_review' ? 'selected' : '' }}>
                                        Send for manual review
                                    </option>
                                    <option value="reject_invoice" {{ $behavior === 'reject_invoice' ? 'selected' : '' }}>
                                        Reject invoice (no cover)
                                    </option>
                                </select>
                                <p class="text-[11px] text-red-700 mt-1">
                                    This only applies once the policy grace period has expired. While grace is active, normal authorization rules apply.
                                </p>
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

            <!-- Visit Authorization Tab -->
            <div id="content-visit-authorization" class="tab-content" style="display: none;">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Visit Authorization Period</h2>
                    <p class="text-sm text-slate-600 mb-6">
                        Configure how long a visit authorization is valid for admitted patients. Data older than this period can be archived or moved to another folder.
                    </p>

                    <form action="{{ route('settings.update-visit-authorization') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_tab" id="current_tab_visit-authorization" value="visit-authorization">

                        <!-- Visit Authorization Period -->
                        <div>
                            <label for="visit_authorization_period_days" class="block text-sm font-medium text-slate-700 mb-2">
                                Authorization Period (Days)
                            </label>
                            <input 
                                type="number" 
                                name="visit_authorization_period_days" 
                                id="visit_authorization_period_days" 
                                value="{{ old('visit_authorization_period_days', $insuranceCompany->visit_authorization_period_days ?? 7) }}"
                                min="1"
                                max="365"
                                class="w-full max-w-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            <p class="text-xs text-slate-500 mt-2">
                                Number of days a visit authorization remains valid for admitted patients. Common values: 3 days, 7 days, 14 days, 30 days.
                            </p>
                        </div>

                        <!-- Show Policy Details at Registration -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div>
                                <label class="text-sm font-medium text-slate-900">Display Policy Details at Registration</label>
                                <p class="text-xs text-slate-600 mt-1">Show client policy details at the registration desk when checking in</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="show_policy_details_at_registration" value="0">
                                <input 
                                    type="checkbox" 
                                    name="show_policy_details_at_registration" 
                                    id="show_policy_details_toggle"
                                    value="1"
                                    {{ old('show_policy_details_at_registration', $insuranceCompany->show_policy_details_at_registration ?? true) ? 'checked' : '' }}
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Select Which Policy Details to Display -->
                        <div id="policy_details_selection" style="display: {{ old('show_policy_details_at_registration', $insuranceCompany->show_policy_details_at_registration ?? true) ? 'block' : 'none' }};">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <label class="text-sm font-medium text-slate-900 mb-3 block">Which policy details should clients see?</label>
                                <p class="text-xs text-slate-600 mb-4">Select the fields to display at the registration desk:</p>
                                
                                <div class="space-y-3">
                                    @php
                                        $detailsOptions = [
                                            'policy_number' => 'Policy Number',
                                            'deductible_amount' => 'Deductible Amount',
                                            'copay_amount' => 'Copay Amount',
                                            'coinsurance_percentage' => 'Coinsurance Percentage',
                                            'copay_max_limit' => 'Copay Max Limit',
                                        ];
                                        $selectedDetails = old('policy_details_to_display_at_registration', $insuranceCompany->policy_details_to_display_at_registration ?? ['policy_number']);
                                    @endphp

                                    @foreach($detailsOptions as $fieldKey => $fieldLabel)
                                        <label class="flex items-center p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                                            <input 
                                                type="checkbox" 
                                                name="policy_details_to_display_at_registration[]" 
                                                value="{{ $fieldKey }}"
                                                {{ in_array($fieldKey, is_array($selectedDetails) ? $selectedDetails : []) ? 'checked' : '' }}
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                                            >
                                            <span class="ml-3 text-sm font-medium text-slate-700">{{ $fieldLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="mt-3 p-3 bg-white rounded border border-blue-100 text-xs text-slate-600">
                                    <p class="font-semibold text-slate-900 mb-1">Preview:</p>
                                    <p>Selected fields will appear on the client's registration confirmation screen</p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">How This Works:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>Visit authorizations for admitted patients expire after the configured number of days</li>
                                        <li>Data older than this period can be archived or moved to a separate folder</li>
                                        <li>This setting applies globally to all vendors in this insurance company</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Example Scenarios -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">Example Scenarios</h3>
                            <div class="space-y-2 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <span><strong>3 days:</strong> Short-term admitted patients</span>
                                    <span class="text-slate-400">Quick discharge expected</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><strong>7 days:</strong> Standard admission period</span>
                                    <span class="text-slate-400">Typical hospital stay</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><strong>14 days:</strong> Extended admissions</span>
                                    <span class="text-slate-400">Complex cases, recovery</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><strong>30 days:</strong> Long-term care</span>
                                    <span class="text-slate-400">Rehabilitation, intensive care</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                                Save Visit Authorization Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showPolicyDetailsToggle = document.getElementById('show_policy_details_toggle');
    const policyDetailsSelection = document.getElementById('policy_details_selection');

    if (showPolicyDetailsToggle) {
        showPolicyDetailsToggle.addEventListener('change', function() {
            if (this.checked) {
                policyDetailsSelection.style.display = 'block';
            } else {
                policyDetailsSelection.style.display = 'none';
            }
        });
    }
});
</script>
