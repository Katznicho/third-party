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

    <!-- Policy Number Generation Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-3">Policy Number Generation</h2>
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


    <!-- Deductible Contribution Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-3">Deductible Contribution Settings</h2>
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

    <!-- Required Client Fields Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-3">Required Client Fields</h2>
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

<script>
    // Update preview on input change
    document.addEventListener('DOMContentLoaded', function() {
        const formatInput = document.getElementById('policy_number_format');
        const randomLengthInput = document.getElementById('policy_number_random_length');
        const randomTypeInput = document.getElementById('policy_number_random_type');
        const companyCodeLengthInput = document.getElementById('policy_number_company_code_length');
        const previewElement = document.getElementById('preview-policy-number');
        
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
        randomLengthInput.addEventListener('input', updatePreview);
        randomTypeInput.addEventListener('change', updatePreview);
        companyCodeLengthInput.addEventListener('input', updatePreview);
    });
</script>
@endsection
