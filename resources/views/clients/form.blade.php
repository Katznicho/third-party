<form action="{{ $action }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <!-- Personal Details Section -->
    <div class="border-b border-slate-200 pb-4">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Personal Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 mb-2">Type <span class="text-red-500">*</span></label>
                <select name="type" id="type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="principal" {{ old('type', $client->type ?? '') === 'principal' ? 'selected' : '' }}>Principal Member</option>
                    <option value="dependent" {{ old('type', $client->type ?? '') === 'dependent' ? 'selected' : '' }}>Dependent</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Principal Member (for dependents) -->
            <div id="principal-member-field" style="display: {{ old('type', $client->type ?? '') === 'dependent' ? 'block' : 'none' }};">
                <label for="principal_member_id" class="block text-sm font-medium text-slate-700 mb-2">Principal Member</label>
                <select name="principal_member_id" id="principal_member_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Principal Member</option>
                    @foreach(\App\Models\Client::where('type', 'principal')->when(isset($client->id) && $client->id, function($q) use ($client) { return $q->where('id', '!=', $client->id); })->get() as $principal)
                        <option value="{{ $principal->id }}" {{ old('principal_member_id', $client->principal_member_id ?? '') == $principal->id ? 'selected' : '' }}>
                            {{ $principal->full_name }} ({{ $principal->id_passport_no }})
                        </option>
                    @endforeach
                </select>
                @error('principal_member_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Plan -->
            <div id="plan-field">
                <label for="plan_id" class="block text-sm font-medium text-slate-700 mb-2">Plan</label>
                <select name="plan_id" id="plan_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Plan</option>
                    @php
                        $plans = \App\Models\Plan::where('insurance_company_id', auth()->user()->insurance_company_id)->where('is_active', true)->orderBy('sort_order')->get();
                    @endphp
                    @foreach($plans as $planItem)
                        <option value="{{ $planItem->id }}" {{ old('plan_id', $client->plan_id ?? '') == $planItem->id ? 'selected' : '' }}>
                            {{ $planItem->name }} ({{ $planItem->code }})
                        </option>
                    @endforeach
                </select>
                @error('plan_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-2">Title</label>
                <select name="title" id="title" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Title</option>
                    <option value="Mr" {{ old('title', $client->title ?? '') === 'Mr' ? 'selected' : '' }}>Mr</option>
                    <option value="Mrs" {{ old('title', $client->title ?? '') === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                    <option value="Miss" {{ old('title', $client->title ?? '') === 'Miss' ? 'selected' : '' }}>Miss</option>
                    <option value="Dr" {{ old('title', $client->title ?? '') === 'Dr' ? 'selected' : '' }}>Dr</option>
                </select>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Surname -->
            <div>
                <label for="surname" class="block text-sm font-medium text-slate-700 mb-2">Surname</label>
                <input type="text" name="surname" id="surname" value="{{ old('surname', $client->surname ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('surname')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-2">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $client->first_name ?? '') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('first_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Other Names -->
            <div>
                <label for="other_names" class="block text-sm font-medium text-slate-700 mb-2">Other Names</label>
                <input type="text" name="other_names" id="other_names" value="{{ old('other_names', $client->other_names ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('other_names')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ID/Passport Number -->
            <div>
                <label for="id_passport_no" class="block text-sm font-medium text-slate-700 mb-2">ID/Passport Number <span class="text-red-500">*</span></label>
                <input type="text" name="id_passport_no" id="id_passport_no" value="{{ old('id_passport_no', $client->id_passport_no ?? '') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('id_passport_no')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Gender -->
            <div>
                <label for="gender" class="block text-sm font-medium text-slate-700 mb-2">Gender</label>
                <select name="gender" id="gender" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Gender</option>
                    <option value="Male" {{ old('gender', $client->gender ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('gender', $client->gender ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                </select>
                @error('gender')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date of Birth -->
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-2">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth ? $client->date_of_birth->format('Y-m-d') : '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('date_of_birth')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Marital Status -->
            <div>
                <label for="marital_status" class="block text-sm font-medium text-slate-700 mb-2">Marital Status</label>
                <select name="marital_status" id="marital_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Status</option>
                    <option value="Single" {{ old('marital_status', $client->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('marital_status', $client->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="Divorced" {{ old('marital_status', $client->marital_status ?? '') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="Widowed" {{ old('marital_status', $client->marital_status ?? '') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                </select>
                @error('marital_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- TIN -->
            <div>
                <label for="tin" class="block text-sm font-medium text-slate-700 mb-2">TIN</label>
                <input type="text" name="tin" id="tin" value="{{ old('tin', $client->tin ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('tin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Height -->
            <div>
                <label for="height" class="block text-sm font-medium text-slate-700 mb-2">Height (ft & inches)</label>
                <input type="text" name="height" id="height" value="{{ old('height', $client->height ?? '') }}" placeholder="e.g., 5'10" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('height')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weight -->
            <div>
                <label for="weight" class="block text-sm font-medium text-slate-700 mb-2">Weight (Kgs)</label>
                <input type="text" name="weight" id="weight" value="{{ old('weight', $client->weight ?? '') }}" placeholder="e.g., 70" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('weight')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Employment & Contact Details Section -->
    <div class="border-b border-slate-200 pb-4">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Employment & Contact Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Employer Name -->
            <div>
                <label for="employer_name" class="block text-sm font-medium text-slate-700 mb-2">Employer Name</label>
                <input type="text" name="employer_name" id="employer_name" value="{{ old('employer_name', $client->employer_name ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('employer_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Occupation -->
            <div>
                <label for="occupation" class="block text-sm font-medium text-slate-700 mb-2">Occupation</label>
                <input type="text" name="occupation" id="occupation" value="{{ old('occupation', $client->occupation ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('occupation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nationality -->
            <div>
                <label for="nationality" class="block text-sm font-medium text-slate-700 mb-2">Nationality</label>
                <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $client->nationality ?? 'Ugandan') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('nationality')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Home Physical Address -->
            <div class="md:col-span-2">
                <label for="home_physical_address" class="block text-sm font-medium text-slate-700 mb-2">Home Physical Address</label>
                <textarea name="home_physical_address" id="home_physical_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('home_physical_address', $client->home_physical_address ?? '') }}</textarea>
                @error('home_physical_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Office Physical Address -->
            <div class="md:col-span-2">
                <label for="office_physical_address" class="block text-sm font-medium text-slate-700 mb-2">Office Physical Address</label>
                <textarea name="office_physical_address" id="office_physical_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('office_physical_address', $client->office_physical_address ?? '') }}</textarea>
                @error('office_physical_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Home Telephone -->
            <div>
                <label for="home_telephone" class="block text-sm font-medium text-slate-700 mb-2">Home Telephone</label>
                <input type="text" name="home_telephone" id="home_telephone" value="{{ old('home_telephone', $client->home_telephone ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('home_telephone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Office Telephone -->
            <div>
                <label for="office_telephone" class="block text-sm font-medium text-slate-700 mb-2">Office Telephone</label>
                <input type="text" name="office_telephone" id="office_telephone" value="{{ old('office_telephone', $client->office_telephone ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('office_telephone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Cell Phone -->
            <div>
                <label for="cell_phone" class="block text-sm font-medium text-slate-700 mb-2">Cell Phone</label>
                <input type="text" name="cell_phone" id="cell_phone" value="{{ old('cell_phone', $client->cell_phone ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('cell_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- WhatsApp Line -->
            <div>
                <label for="whatsapp_line" class="block text-sm font-medium text-slate-700 mb-2">WhatsApp Line</label>
                <input type="text" name="whatsapp_line" id="whatsapp_line" value="{{ old('whatsapp_line', $client->whatsapp_line ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('whatsapp_line')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $client->email ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Relation to Principal (for dependents) -->
            <div id="relation-field" style="display: {{ old('type', $client->type ?? '') === 'dependent' ? 'block' : 'none' }};">
                <label for="relation_to_principal" class="block text-sm font-medium text-slate-700 mb-2">Relation to Principal</label>
                <select name="relation_to_principal" id="relation_to_principal" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Relation</option>
                    <option value="Spouse" {{ old('relation_to_principal', $client->relation_to_principal ?? '') === 'Spouse' ? 'selected' : '' }}>Spouse</option>
                    <option value="Child" {{ old('relation_to_principal', $client->relation_to_principal ?? '') === 'Child' ? 'selected' : '' }}>Child</option>
                    <option value="Parent" {{ old('relation_to_principal', $client->relation_to_principal ?? '') === 'Parent' ? 'selected' : '' }}>Parent</option>
                    <option value="Sibling" {{ old('relation_to_principal', $client->relation_to_principal ?? '') === 'Sibling' ? 'selected' : '' }}>Sibling</option>
                    <option value="Other" {{ old('relation_to_principal', $client->relation_to_principal ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('relation_to_principal')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Next of Kin Section -->
    <div class="border-b border-slate-200 pb-4">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Next of Kin Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Next of Kin Title -->
            <div>
                <label for="next_of_kin_title" class="block text-sm font-medium text-slate-700 mb-2">Title</label>
                <select name="next_of_kin_title" id="next_of_kin_title" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Title</option>
                    <option value="Mr" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Mr' ? 'selected' : '' }}>Mr</option>
                    <option value="Mrs" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                    <option value="Miss" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Miss' ? 'selected' : '' }}>Miss</option>
                    <option value="Dr" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Dr' ? 'selected' : '' }}>Dr</option>
                </select>
            </div>

            <!-- Next of Kin Surname -->
            <div>
                <label for="next_of_kin_surname" class="block text-sm font-medium text-slate-700 mb-2">Surname</label>
                <input type="text" name="next_of_kin_surname" id="next_of_kin_surname" value="{{ old('next_of_kin_surname', $client->next_of_kin_surname ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin First Name -->
            <div>
                <label for="next_of_kin_first_name" class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                <input type="text" name="next_of_kin_first_name" id="next_of_kin_first_name" value="{{ old('next_of_kin_first_name', $client->next_of_kin_first_name ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Other Names -->
            <div>
                <label for="next_of_kin_other_names" class="block text-sm font-medium text-slate-700 mb-2">Other Names</label>
                <input type="text" name="next_of_kin_other_names" id="next_of_kin_other_names" value="{{ old('next_of_kin_other_names', $client->next_of_kin_other_names ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Relation -->
            <div>
                <label for="next_of_kin_relation" class="block text-sm font-medium text-slate-700 mb-2">Relation</label>
                <input type="text" name="next_of_kin_relation" id="next_of_kin_relation" value="{{ old('next_of_kin_relation', $client->next_of_kin_relation ?? '') }}" placeholder="e.g., Spouse, Parent, Sibling" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin ID/Passport -->
            <div>
                <label for="next_of_kin_id_passport_no" class="block text-sm font-medium text-slate-700 mb-2">ID/Passport Number</label>
                <input type="text" name="next_of_kin_id_passport_no" id="next_of_kin_id_passport_no" value="{{ old('next_of_kin_id_passport_no', $client->next_of_kin_id_passport_no ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Cell Phone -->
            <div>
                <label for="next_of_kin_cell_phone" class="block text-sm font-medium text-slate-700 mb-2">Cell Phone</label>
                <input type="text" name="next_of_kin_cell_phone" id="next_of_kin_cell_phone" value="{{ old('next_of_kin_cell_phone', $client->next_of_kin_cell_phone ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Email -->
            <div>
                <label for="next_of_kin_email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                <input type="email" name="next_of_kin_email" id="next_of_kin_email" value="{{ old('next_of_kin_email', $client->next_of_kin_email ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Post Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_post_address" class="block text-sm font-medium text-slate-700 mb-2">Postal Address</label>
                <textarea name="next_of_kin_post_address" id="next_of_kin_post_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('next_of_kin_post_address', $client->next_of_kin_post_address ?? '') }}</textarea>
            </div>

            <!-- Next of Kin Physical Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_physical_address" class="block text-sm font-medium text-slate-700 mb-2">Physical Address</label>
                <textarea name="next_of_kin_physical_address" id="next_of_kin_physical_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('next_of_kin_physical_address', $client->next_of_kin_physical_address ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <!-- Options Section -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Options</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Has Deductible -->
            <div class="flex items-center">
                <input type="checkbox" name="has_deductible" id="has_deductible" value="1" {{ old('has_deductible', $client->has_deductible ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                <label for="has_deductible" class="ml-2 block text-sm text-slate-700">Has Deductible</label>
            </div>

            <!-- Deductible Amount -->
            <div id="deductible-amount-field" style="display: {{ old('has_deductible', $client->has_deductible ?? false) ? 'block' : 'none' }};">
                <label for="deductible_amount" class="block text-sm font-medium text-slate-700 mb-2">Deductible Amount (UGX)</label>
                <input type="number" name="deductible_amount" id="deductible_amount" value="{{ old('deductible_amount', $client->deductible_amount ?? 100000) }}" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('deductible_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Telemedicine Only -->
            <div class="flex items-center">
                <input type="checkbox" name="telemedicine_only" id="telemedicine_only" value="1" {{ old('telemedicine_only', $client->telemedicine_only ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                <label for="telemedicine_only" class="ml-2 block text-sm text-slate-700">Telemedicine Only</label>
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $client->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-slate-700">Active</label>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-end gap-4 pt-4 border-t border-slate-200">
        <a href="{{ route('clients.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
            Cancel
        </a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
            {{ $method === 'PUT' ? 'Update' : 'Create' }} Client
        </button>
    </div>
</form>

<script>
    // Show/hide principal member field based on type
    document.getElementById('type').addEventListener('change', function() {
        const principalField = document.getElementById('principal-member-field');
        const relationField = document.getElementById('relation-field');
        if (this.value === 'dependent') {
            principalField.style.display = 'block';
            relationField.style.display = 'block';
            document.getElementById('principal_member_id').required = true;
            document.getElementById('relation_to_principal').required = true;
        } else {
            principalField.style.display = 'none';
            relationField.style.display = 'none';
            document.getElementById('principal_member_id').required = false;
            document.getElementById('relation_to_principal').required = false;
        }
    });

    // Show/hide deductible amount field
    document.getElementById('has_deductible').addEventListener('change', function() {
        const deductibleField = document.getElementById('deductible-amount-field');
        if (this.checked) {
            deductibleField.style.display = 'block';
        } else {
            deductibleField.style.display = 'none';
        }
    });
</script>
