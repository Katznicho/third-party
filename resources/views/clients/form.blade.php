<form action="{{ $action }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-8" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <!-- Header Section -->
    <div class="text-center border-b border-slate-300 pb-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">HEALTH INSURANCE APPLICATION FORM</h1>
        <p class="text-sm text-slate-600">Please fill out ALL the spaces provided on this application form using BLOCK letters. Any blank spaces will be interpreted to mean that there was nothing to declare.</p>
    </div>

    <!-- 1. Principal Member Details Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">1. Principal Member Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                <select name="title" id="title" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Title</option>
                    <option value="Mr" {{ old('title', $client->title ?? '') === 'Mr' ? 'selected' : '' }}>Mr</option>
                    <option value="Mrs" {{ old('title', $client->title ?? '') === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                    <option value="Miss" {{ old('title', $client->title ?? '') === 'Miss' ? 'selected' : '' }}>Miss</option>
                    <option value="Dr" {{ old('title', $client->title ?? '') === 'Dr' ? 'selected' : '' }}>Dr</option>
                </select>
            </div>

            <!-- Surname -->
            <div>
                <label for="surname" class="block text-sm font-medium text-slate-700 mb-1">Surname</label>
                <input type="text" name="surname" id="surname" value="{{ old('surname', $client->surname ?? '') }}" placeholder="Enter surname in BLOCK letters" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" style="text-transform: uppercase;">
            </div>

            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $client->first_name ?? '') }}" placeholder="Enter first name in BLOCK letters" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" style="text-transform: uppercase;">
                @error('first_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Other Names -->
            <div>
                <label for="other_names" class="block text-sm font-medium text-slate-700 mb-1">Other Names</label>
                <input type="text" name="other_names" id="other_names" value="{{ old('other_names', $client->other_names ?? '') }}" placeholder="Enter other names in BLOCK letters" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" style="text-transform: uppercase;">
            </div>

            <!-- ID/Passport Number -->
            <div>
                <label for="id_passport_no" class="block text-sm font-medium text-slate-700 mb-1">ID / Passport No. <span class="text-red-500">*</span></label>
                <input type="text" name="id_passport_no" id="id_passport_no" value="{{ old('id_passport_no', $client->id_passport_no ?? '') }}" placeholder="Enter ID or passport number" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('id_passport_no')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Gender -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Gender</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="gender" value="Male" {{ old('gender', $client->gender ?? '') === 'Male' ? 'checked' : '' }} class="mr-2">
                        <span>Male</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="gender" value="Female" {{ old('gender', $client->gender ?? '') === 'Female' ? 'checked' : '' }} class="mr-2">
                        <span>Female</span>
                    </label>
                </div>
            </div>

            <!-- TIN -->
            <div>
                <label for="tin" class="block text-sm font-medium text-slate-700 mb-1">TIN</label>
                <input type="text" name="tin" id="tin" value="{{ old('tin', $client->tin ?? '') }}" placeholder="Enter TIN number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Date of Birth -->
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth ? $client->date_of_birth->format('Y-m-d') : '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Marital Status -->
            <div>
                <label for="marital_status" class="block text-sm font-medium text-slate-700 mb-1">Marital Status</label>
                <select name="marital_status" id="marital_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Status</option>
                    <option value="Single" {{ old('marital_status', $client->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('marital_status', $client->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="Divorced" {{ old('marital_status', $client->marital_status ?? '') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="Widowed" {{ old('marital_status', $client->marital_status ?? '') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                </select>
            </div>

            <!-- Height -->
            <div>
                <label for="height" class="block text-sm font-medium text-slate-700 mb-1">Height (ft & inches)</label>
                <input type="text" name="height" id="height" value="{{ old('height', $client->height ?? '') }}" placeholder="e.g., 5'10" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Weight -->
            <div>
                <label for="weight" class="block text-sm font-medium text-slate-700 mb-1">Weight (Kgs)</label>
                <input type="text" name="weight" id="weight" value="{{ old('weight', $client->weight ?? '') }}" placeholder="e.g., 70" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Employer Name -->
            <div>
                <label for="employer_name" class="block text-sm font-medium text-slate-700 mb-1">Name of Employer (if employed)</label>
                <input type="text" name="employer_name" id="employer_name" value="{{ old('employer_name', $client->employer_name ?? '') }}" placeholder="Enter employer name" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Occupation -->
            <div>
                <label for="occupation" class="block text-sm font-medium text-slate-700 mb-1">Occupation</label>
                <input type="text" name="occupation" id="occupation" value="{{ old('occupation', $client->occupation ?? '') }}" placeholder="Enter occupation" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Nationality -->
            <div>
                <label for="nationality" class="block text-sm font-medium text-slate-700 mb-1">Nationality</label>
                <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $client->nationality ?? 'Ugandan') }}" placeholder="Enter nationality" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- CONTACT DETAILS Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">CONTACT DETAILS</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Home Physical Address -->
            <div>
                <label for="home_physical_address" class="block text-sm font-medium text-slate-700 mb-1">Home Physical Address</label>
                <textarea name="home_physical_address" id="home_physical_address" rows="2" placeholder="Enter home physical address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('home_physical_address', $client->home_physical_address ?? '') }}</textarea>
            </div>

            <!-- Office Physical Address -->
            <div>
                <label for="office_physical_address" class="block text-sm font-medium text-slate-700 mb-1">Office Physical Address</label>
                <textarea name="office_physical_address" id="office_physical_address" rows="2" placeholder="Enter office physical address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('office_physical_address', $client->office_physical_address ?? '') }}</textarea>
            </div>

            <!-- Home Telephone -->
            <div>
                <label for="home_telephone" class="block text-sm font-medium text-slate-700 mb-1">Home Telephone</label>
                <input type="text" name="home_telephone" id="home_telephone" value="{{ old('home_telephone', $client->home_telephone ?? '') }}" placeholder="Enter home telephone" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Office Telephone -->
            <div>
                <label for="office_telephone" class="block text-sm font-medium text-slate-700 mb-1">Office Telephone</label>
                <input type="text" name="office_telephone" id="office_telephone" value="{{ old('office_telephone', $client->office_telephone ?? '') }}" placeholder="Enter office telephone" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Cell Phone -->
            <div>
                <label for="cell_phone" class="block text-sm font-medium text-slate-700 mb-1">Cell Phone</label>
                <input type="text" name="cell_phone" id="cell_phone" value="{{ old('cell_phone', $client->cell_phone ?? '') }}" placeholder="Enter cell phone number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- WhatsApp Line -->
            <div>
                <label for="whatsapp_line" class="block text-sm font-medium text-slate-700 mb-1">WhatsApp Line</label>
                <input type="text" name="whatsapp_line" id="whatsapp_line" value="{{ old('whatsapp_line', $client->whatsapp_line ?? '') }}" placeholder="Enter WhatsApp number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $client->email ?? '') }}" placeholder="Enter email address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- NEXT OF KIN DETAILS Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">NEXT OF KIN DETAILS</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Next of Kin Title -->
            <div>
                <label for="next_of_kin_title" class="block text-sm font-medium text-slate-700 mb-1">Title</label>
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
                <label for="next_of_kin_surname" class="block text-sm font-medium text-slate-700 mb-1">Surname</label>
                <input type="text" name="next_of_kin_surname" id="next_of_kin_surname" value="{{ old('next_of_kin_surname', $client->next_of_kin_surname ?? '') }}" placeholder="Enter next of kin surname" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin First Name -->
            <div>
                <label for="next_of_kin_first_name" class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                <input type="text" name="next_of_kin_first_name" id="next_of_kin_first_name" value="{{ old('next_of_kin_first_name', $client->next_of_kin_first_name ?? '') }}" placeholder="Enter next of kin first name" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Other Names -->
            <div>
                <label for="next_of_kin_other_names" class="block text-sm font-medium text-slate-700 mb-1">Other Names</label>
                <input type="text" name="next_of_kin_other_names" id="next_of_kin_other_names" value="{{ old('next_of_kin_other_names', $client->next_of_kin_other_names ?? '') }}" placeholder="Enter next of kin other names" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Relation -->
            <div>
                <label for="next_of_kin_relation" class="block text-sm font-medium text-slate-700 mb-1">Relation to Principal Member</label>
                <input type="text" name="next_of_kin_relation" id="next_of_kin_relation" value="{{ old('next_of_kin_relation', $client->next_of_kin_relation ?? '') }}" placeholder="e.g., Spouse, Parent, Sibling" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin ID/Passport -->
            <div>
                <label for="next_of_kin_id_passport_no" class="block text-sm font-medium text-slate-700 mb-1">Passport / ID No.</label>
                <input type="text" name="next_of_kin_id_passport_no" id="next_of_kin_id_passport_no" value="{{ old('next_of_kin_id_passport_no', $client->next_of_kin_id_passport_no ?? '') }}" placeholder="Enter next of kin ID or passport number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Cell Phone -->
            <div>
                <label for="next_of_kin_cell_phone" class="block text-sm font-medium text-slate-700 mb-1">Cell Phone</label>
                <input type="text" name="next_of_kin_cell_phone" id="next_of_kin_cell_phone" value="{{ old('next_of_kin_cell_phone', $client->next_of_kin_cell_phone ?? '') }}" placeholder="Enter next of kin cell phone" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Email -->
            <div>
                <label for="next_of_kin_email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="next_of_kin_email" id="next_of_kin_email" value="{{ old('next_of_kin_email', $client->next_of_kin_email ?? '') }}" placeholder="Enter next of kin email address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Next of Kin Post Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_post_address" class="block text-sm font-medium text-slate-700 mb-1">Post Address</label>
                <textarea name="next_of_kin_post_address" id="next_of_kin_post_address" rows="2" placeholder="Enter next of kin postal address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('next_of_kin_post_address', $client->next_of_kin_post_address ?? '') }}</textarea>
            </div>

            <!-- Next of Kin Physical Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_physical_address" class="block text-sm font-medium text-slate-700 mb-1">Physical Address</label>
                <textarea name="next_of_kin_physical_address" id="next_of_kin_physical_address" rows="2" placeholder="Enter next of kin physical address" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('next_of_kin_physical_address', $client->next_of_kin_physical_address ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <!-- DETAILS OF BENEFICIARIES Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">DETAILS OF BENEFICIARIES</h2>
        <p class="text-sm text-slate-600 mb-4">Please provide details for up to 8 dependants</p>
        
        <div id="dependants-container" class="space-y-6">
            <!-- Dependant fields will be added dynamically via JavaScript -->
            <!-- For now, we'll show one dependant section that can be cloned -->
            <div class="dependant-section border border-slate-200 rounded-lg p-4 bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-slate-900">Dependant 1</h3>
                    <button type="button" onclick="removeDependant(this)" class="text-red-600 hover:text-red-800 text-sm hidden remove-btn">Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                        <select name="dependants[0][title]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Title</option>
                            <option value="Mr">Mr</option>
                            <option value="Mrs">Mrs</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr">Dr</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Surname</label>
                        <input type="text" name="dependants[0][surname]" placeholder="Enter surname" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                        <input type="text" name="dependants[0][first_name]" placeholder="Enter first name" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Other Names</label>
                        <input type="text" name="dependants[0][other_names]" placeholder="Enter other names" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ID / Passport No.</label>
                        <input type="text" name="dependants[0][id_passport_no]" placeholder="Enter ID or passport number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="dependants[0][gender]" value="Male" class="mr-2">
                                <span>Male</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="dependants[0][gender]" value="Female" class="mr-2">
                                <span>Female</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date of Birth</label>
                        <input type="date" name="dependants[0][date_of_birth]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Relation to Principal Member</label>
                        <input type="text" name="dependants[0][relation_to_principal]" placeholder="e.g., Spouse, Child" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Marital Status</label>
                        <select name="dependants[0][marital_status]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Occupation</label>
                        <input type="text" name="dependants[0][occupation]" placeholder="Enter occupation" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Height (ft & inches)</label>
                        <input type="text" name="dependants[0][height]" placeholder="e.g., 5'10" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Weight (Kgs)</label>
                        <input type="text" name="dependants[0][weight]" placeholder="e.g., 70" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>
        <button type="button" onclick="addDependant()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" id="add-dependant-btn">+ Add Dependant</button>
    </div>

    <!-- PREMIUM COMPUTATION Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">PREMIUM COMPUTATION</h2>
        
        <!-- Co-payment, Coinsurance, and Deductible Options -->
        <div class="space-y-6">
            <!-- Co-payment (Copay) -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-sm font-medium text-slate-700 mb-2">Co-payment (Copay)</label>
                <p class="text-xs text-slate-600 mb-3">Fixed amount payable at each visit (e.g., 20,000 per visit)</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="copay_amount" class="block text-sm font-medium text-slate-700 mb-1">Copay Amount (UGX)</label>
                        <input type="number" name="copay_amount" id="copay_amount" value="{{ old('copay_amount', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->copay_amount : '') }}" placeholder="Enter copay amount per visit" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="copay_max_limit" class="block text-sm font-medium text-slate-700 mb-1">Copay Maximum Limit (UGX) - Optional</label>
                        <input type="number" name="copay_max_limit" id="copay_max_limit" value="{{ old('copay_max_limit', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->copay_max_limit : '') }}" placeholder="Enter maximum copay limit (cap)" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-slate-500 mt-1">Maximum total copay amount per policy period</p>
                    </div>
                </div>
            </div>

            <!-- Coinsurance -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-sm font-medium text-slate-700 mb-2">Coinsurance</label>
                <p class="text-xs text-slate-600 mb-3">Fixed percentage paid on all invoices of a particular visit (e.g., 10% means client pays 10% of each invoice)</p>
                <div>
                    <label for="coinsurance_percentage" class="block text-sm font-medium text-slate-700 mb-1">Coinsurance Percentage (%)</label>
                    <input type="number" name="coinsurance_percentage" id="coinsurance_percentage" value="{{ old('coinsurance_percentage', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->coinsurance_percentage : '') }}" placeholder="Enter coinsurance percentage" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Deductible Option -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-sm font-medium text-slate-700 mb-2">Deductible</label>
                <p class="text-xs text-slate-600 mb-3">Amount a client has to pay before the insurance starts paying (e.g., 100,000 per year)</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">WOULD YOU LIKE A DEDUCTIBLE? PLEASE TICK APPLICABLE CHOICE:</label>
                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="radio" name="has_deductible" value="1" {{ old('has_deductible', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->has_deductible : false) ? 'checked' : '' }} class="mr-2">
                            <span>YES</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="has_deductible" value="0" {{ !old('has_deductible', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->has_deductible : false) ? 'checked' : '' }} class="mr-2">
                            <span>NO</span>
                        </label>
                    </div>
                </div>
                <div id="deductible-amount-field" class="mt-4" style="display: {{ old('has_deductible', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->has_deductible : false) ? 'block' : 'none' }};">
                    <label for="deductible_amount" class="block text-sm font-medium text-slate-700 mb-1">Deductible Amount (UGX)</label>
                    <input type="number" name="deductible_amount" id="deductible_amount" value="{{ old('deductible_amount', isset($client) && $client->policies->isNotEmpty() ? $client->policies->first()->deductible_amount : 100000) }}" placeholder="Enter deductible amount" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-slate-500 mt-1">Amount that must be paid before insurance coverage begins</p>
                </div>
            </div>
        </div>

        <!-- Telemedicine Option -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">WOULD YOU LIKE YOUR SELECTED OUTPATIENT PLAN TO RUN EXCLUSIVELY ON TELEMEDICINE?</label>
            <div class="flex gap-6">
                <label class="flex items-center">
                    <input type="radio" name="telemedicine_only" value="1" {{ old('telemedicine_only', $client->telemedicine_only ?? false) ? 'checked' : '' }} class="mr-2">
                    <span>YES</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="telemedicine_only" value="0" {{ !old('telemedicine_only', $client->telemedicine_only ?? false) ? 'checked' : '' }} class="mr-2">
                    <span>NO</span>
                </label>
            </div>
            <p class="text-xs text-slate-600 mt-2">You can choose to receive treatment and diagnosis services from the comfort of your home or office. All you need is your cellphone and you can reach doctors who will extend consultations to you and your eligible family members covered on the plan.</p>
            </div>

        <!-- Plan Selection with Benefits Table -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">PLEASE SELECT YOUR PREFERRED BENEFITS BY CHECKING THE RELEVANT BOX</h3>
            @php
                $plans = \App\Models\Plan::where('insurance_company_id', auth()->user()->insurance_company_id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
                
                // Get standard service categories in the correct order
                $standardCategories = ['Inpatient', 'Outpatient', 'Funeral Expenses', 'Maternity', 'Optical', 'Dental'];
                $serviceCategories = \App\Models\ServiceCategory::whereIn('name', $standardCategories)
                    ->where('is_active', true)
                    ->orderByRaw("FIELD(name, '" . implode("','", $standardCategories) . "')")
                    ->get();
            @endphp
            
            <div class="overflow-x-auto border border-slate-300 rounded-lg">
                <table class="w-full text-sm bg-white">
                    <thead class="bg-slate-200">
                        <tr>
                            <th class="border border-slate-300 px-4 py-3 text-left font-bold text-slate-900">BENEFIT AMOUNT (UGX)</th>
                            @foreach($serviceCategories as $category)
                                <th class="border border-slate-300 px-3 py-3 text-center font-bold text-slate-900 whitespace-nowrap">
                                    @if($category->name === 'Funeral Expenses')
                                        Funeral<br>Expenses
                                    @else
                                        {{ strtoupper($category->name) }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            @php
                                $planCategories = $plan->serviceCategories->keyBy('name');
                                $isSelected = old('plan_id', $client->plan_id ?? '') == $plan->id;
                            @endphp
                            <tr class="hover:bg-blue-50 transition-colors {{ $isSelected ? 'bg-blue-100' : '' }}" data-plan-id="{{ $plan->id }}">
                                <td class="border border-slate-300 px-4 py-3 bg-slate-50">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="plan_id" value="{{ $plan->id }}" id="plan_{{ $plan->id }}" 
                                               {{ $isSelected ? 'checked' : '' }} 
                                               required
                                               class="plan-radio mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300"
                                               data-plan="{{ $plan->id }}">
                                        <span class="font-bold text-slate-900 text-base">{{ $plan->name }}</span>
                                    </label>
                                </td>
                                @foreach($serviceCategories as $category)
                                    @php
                                        $pivot = $planCategories->get($category->name);
                                        $benefitAmount = $pivot ? ($pivot->pivot->benefit_amount ?? 0) : 0;
                                        $isInpatient = $category->name === 'Inpatient';
                                        $isOptical = $category->name === 'Optical';
                                        $isDental = $category->name === 'Dental';
                                        $oldSelected = old('selected_benefits.' . $plan->id . '.' . $category->id, false);
                                    @endphp
                                    <td class="border border-slate-300 px-3 py-3 text-center font-medium">
                                        @if($benefitAmount > 0)
                                            <div class="flex flex-col items-center justify-center space-y-1">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" 
                                                           name="selected_benefits[{{ $plan->id }}][{{ $category->id }}]" 
                                                           value="{{ $benefitAmount }}"
                                                           data-plan="{{ $plan->id }}"
                                                           data-category="{{ $category->id }}"
                                                           data-category-name="{{ $category->name }}"
                                                           data-optical="{{ $isOptical ? '1' : '0' }}"
                                                           data-dental="{{ $isDental ? '1' : '0' }}"
                                                           class="benefit-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 {{ $isInpatient ? 'inpatient-checkbox' : '' }}"
                                                           {{ $isInpatient ? 'checked required' : '' }}
                                                           {{ $oldSelected ? 'checked' : '' }}
                                                           {{ !$isSelected ? 'disabled' : '' }}>
                                                    <span class="ml-1 text-xs text-slate-600">Select</span>
                                                </label>
                                                <span class="text-xs font-semibold text-slate-900 mt-1">{{ number_format($benefitAmount, 0, '.', ',') }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('plan_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-4 text-xs text-slate-600">
                <strong>Please note the following:</strong><br>
                1. Inpatient is a mandatory benefit. All other benefits are optional<br>
                2. Combining benefits from different plans is not permitted<br>
                3. The same plan applies to all members on the same policy<br>
                4. To benefit from maternity cover, you will have to start paying for it in both the policy year prior to and on the policy year that you intend to benefit from it. Maternity benefit is offered to principal members and spouses only<br>
                5. Optical and Dental benefit benefits have to be selected together
            </p>
        </div>

        <!-- Premium Calculation Display -->
        <div id="premium-calculation" class="mt-6 border-2 border-blue-500 rounded-lg p-6 bg-blue-50" style="display: none;">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-6 5h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Premium Calculation
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="number_of_dependents" class="block text-sm font-medium text-slate-700 mb-2">Number of Dependents</label>
                    <input type="number" name="number_of_dependents" id="number_of_dependents" value="{{ old('number_of_dependents', 0) }}" min="0" max="20" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="calculatePremium()">
                    <p class="text-xs text-slate-500 mt-1">Include spouse and children</p>
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 border border-slate-200">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Base Premium (Principal Member):</span>
                        <span class="text-sm font-bold text-slate-900" id="base-premium">UGX 0.00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Dependents Premium (<span id="dependents-count">0</span> dependents):</span>
                        <span class="text-sm font-bold text-slate-900" id="dependents-premium">UGX 0.00</span>
                    </div>
                    <div class="border-t border-slate-300 pt-3 flex justify-between items-center">
                        <span class="text-base font-semibold text-slate-900">Subtotal Premium:</span>
                        <span class="text-base font-bold text-blue-600" id="subtotal-premium">UGX 0.00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Insurance Training Levy (0.5%):</span>
                        <span class="text-sm font-bold text-slate-900" id="training-levy">UGX 0.00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Stamp Duty:</span>
                        <span class="text-sm font-bold text-slate-900" id="stamp-duty">UGX 35,000.00</span>
                    </div>
                    <div class="border-t-2 border-blue-500 pt-3 flex justify-between items-center bg-blue-50 -mx-4 -mb-4 px-4 py-3 rounded-b-lg">
                        <span class="text-lg font-bold text-slate-900">Total Premium Due:</span>
                        <span class="text-xl font-bold text-blue-600" id="total-premium-due">UGX 0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-xs text-yellow-800">
                    <strong>Note:</strong> This is an estimated premium calculation. Final amounts may vary based on additional factors.
                </p>
            </div>
        </div>
    </div>

    <!-- CONFIDENTIAL MEDICAL HISTORY Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">CONFIDENTIAL MEDICAL HISTORY</h2>
        <p class="text-sm text-slate-600 mb-4">State whether you as the principal member or any of your listed dependants have ever been treated or are currently receiving medical treatment, or expect to receive medical treatment for any of the following illnesses including but not limited to:</p>
        
        <div class="space-y-4">
            @if(isset($medicalQuestions) && $medicalQuestions->count() > 0)
                @foreach($medicalQuestions as $question)
                    @php
                        $existingResponse = isset($client) && $client->medicalQuestionResponses ? $client->medicalQuestionResponses->firstWhere('medical_question_id', $question->id) : null;
                        $responseValue = $existingResponse ? $existingResponse->response : null;
                        $showAdditionalInfo = $existingResponse && ($responseValue === 'yes' || !empty($responseValue));
                    @endphp
                    <div class="border border-slate-200 rounded-lg p-4 bg-white {{ $question->has_exclusion_list ? 'border-l-4 border-l-red-500' : '' }}">
                        <div class="flex items-start justify-between mb-3">
                            <p class="text-sm font-medium text-slate-700 flex-1">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                            @if($question->has_exclusion_list)
                                <span class="ml-2 px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">Exclusion List</span>
                            @endif
                        </div>
                        
                        @if($question->question_type === 'yes_no')
                            <div class="flex gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="medical_questions[{{ $question->id }}][response]" value="yes" {{ $responseValue === 'yes' ? 'checked' : '' }} class="mr-2 question-response" data-question-id="{{ $question->id }}">
                                    <span>YES</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="medical_questions[{{ $question->id }}][response]" value="no" {{ $responseValue !== 'yes' ? 'checked' : '' }} class="mr-2 question-response" data-question-id="{{ $question->id }}">
                                    <span>NO</span>
                                </label>
                            </div>
                        @elseif($question->question_type === 'text')
                            <textarea name="medical_questions[{{ $question->id }}][response]" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 question-response" data-question-id="{{ $question->id }}" placeholder="Enter your response">{{ $responseValue }}</textarea>
                        @elseif($question->question_type === 'date')
                            <input type="date" name="medical_questions[{{ $question->id }}][response]" value="{{ $responseValue }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 question-response" data-question-id="{{ $question->id }}">
                        @elseif($question->question_type === 'number')
                            <input type="number" name="medical_questions[{{ $question->id }}][response]" value="{{ $responseValue }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 question-response" data-question-id="{{ $question->id }}" placeholder="Enter number">
                        @endif

                        @if($question->requires_additional_info)
                            @php
                                $additionalInfo = $existingResponse && $existingResponse->additional_info ? $existingResponse->additional_info : null;
                            @endphp
                            <div class="mt-3 additional-info-field" id="additional-info-{{ $question->id }}" style="display: {{ $showAdditionalInfo ? 'block' : 'none' }};">
                                @if($question->additional_info_type === 'date')
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $question->additional_info_label ?? 'Date' }}</label>
                                    <input type="date" name="medical_questions[{{ $question->id }}][additional_info]" value="{{ is_array($additionalInfo) ? ($additionalInfo['date'] ?? '') : $additionalInfo }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @elseif($question->additional_info_type === 'table')
                                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ $question->additional_info_label ?? 'Details' }}</label>
                                    <table class="w-full border border-slate-300">
                                        <thead class="bg-slate-100">
                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 text-left text-sm">Applicant Name</th>
                                                <th class="border border-slate-300 px-3 py-2 text-left text-sm">Prescribed Medication</th>
                                                <th class="border border-slate-300 px-3 py-2 text-left text-sm">Diagnosis</th>
                                                <th class="border border-slate-300 px-3 py-2 text-left text-sm">Date Started/To Be Started</th>
                                            </tr>
                                        </thead>
                                        <tbody id="medication-tbody-{{ $question->id }}">
                                            @if(is_array($additionalInfo) && count($additionalInfo) > 0)
                                                @foreach($additionalInfo as $index => $med)
                                                    <tr>
                                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][{{ $index }}][applicant_name]" value="{{ $med['applicant_name'] ?? '' }}" placeholder="Enter applicant name" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][{{ $index }}][medication]" value="{{ $med['medication'] ?? '' }}" placeholder="Enter medication" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][{{ $index }}][diagnosis]" value="{{ $med['diagnosis'] ?? '' }}" placeholder="Enter diagnosis" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                        <td class="border border-slate-300 px-3 py-2"><input type="date" name="medications[{{ $question->id }}][{{ $index }}][date_started]" value="{{ $med['date_started'] ?? '' }}" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][0][applicant_name]" placeholder="Enter applicant name" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                    <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][0][medication]" placeholder="Enter medication" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                    <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[{{ $question->id }}][0][diagnosis]" placeholder="Enter diagnosis" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                    <td class="border border-slate-300 px-3 py-2"><input type="date" name="medications[{{ $question->id }}][0][date_started]" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    <button type="button" onclick="addMedicationRow({{ $question->id }})" class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Add Row</button>
                                @else
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $question->additional_info_label ?? 'Additional Information' }}</label>
                                    <textarea name="medical_questions[{{ $question->id }}][additional_info]" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter additional information">{{ is_array($additionalInfo) ? json_encode($additionalInfo) : $additionalInfo }}</textarea>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">No medical questions have been configured. Please contact your administrator.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- DECLARATION Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">DECLARATION</h2>
        <p class="text-sm text-slate-600 mb-4">Please note that this application form is part of the insurance contract</p>
        <p class="text-sm text-slate-700 mb-4">I hereby declare that the statements in this form are true and complete. I further declare that I have not withheld any material information in regard to this application that ought to be disclosed. I have read, understood and agree with the cover options, terms and conditions as stipulated in the product and I agree to abide by the rules governing this policy and further agree that the answers given in this declaration and answers given in this application form shall be the basis of the contract between the insurance company and I.</p>
        <p class="text-sm text-slate-700 mb-4">I consent to the insurance company seeking information from any doctor, hospital or clinic I or any of my family members may have consulted or from any insurer from whom I have requested insurance and I hereby authorize the giving of such information to the insurance company.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div>
                <label for="desired_start_date" class="block text-sm font-medium text-slate-700 mb-1">Desired Start Date</label>
                <input type="date" name="desired_start_date" id="desired_start_date" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="agent_broker_name" class="block text-sm font-medium text-slate-700 mb-1">Agent/Broker Name (if applicable)</label>
                <input type="text" name="agent_broker_name" id="agent_broker_name" placeholder="Enter agent/broker name" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Hidden fields for type -->
    <input type="hidden" name="type" value="principal">
    <input type="hidden" name="is_active" value="1">

    <!-- Form Actions -->
    <div class="flex justify-end gap-4 pt-4 border-t border-slate-200">
        <a href="{{ route('clients.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
            Cancel
        </a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
            {{ $method === 'PUT' ? 'Update' : 'Submit' }} Application
        </button>
    </div>
</form>

<script>
    let dependantCount = 1;
    let medicationCount = 1;

    // Add dependant
    function addDependant() {
        if (dependantCount >= 8) {
            alert('Maximum of 8 dependants allowed');
            return;
        }
        
        const container = document.getElementById('dependants-container');
        const template = document.querySelector('.dependant-section').cloneNode(true);
        template.querySelector('h3').textContent = `Dependant ${dependantCount + 1}`;
        
        // Update all input names with new index
        const inputs = template.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/\[0\]/, `[${dependantCount}]`);
            }
        });
        
        // Show remove button
        template.querySelector('.remove-btn').classList.remove('hidden');
        
        container.appendChild(template);
        dependantCount++;
        
        if (dependantCount >= 8) {
            document.getElementById('add-dependant-btn').style.display = 'none';
        }
    }

    // Remove dependant
    function removeDependant(btn) {
        btn.closest('.dependant-section').remove();
        dependantCount--;
        document.getElementById('add-dependant-btn').style.display = 'block';
        
        // Renumber dependants
        const sections = document.querySelectorAll('.dependant-section');
        sections.forEach((section, index) => {
            section.querySelector('h3').textContent = `Dependant ${index + 1}`;
            const inputs = section.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.name && input.name.includes('dependants[')) {
                    input.name = input.name.replace(/dependants\[\d+\]/, `dependants[${index}]`);
                }
            });
        });
    }

    // Add medication row for dynamic questions
    let medicationRowCounters = {};
    function addMedicationRow(questionId) {
        if (!medicationRowCounters[questionId]) {
            medicationRowCounters[questionId] = 1;
        }
        const tbody = document.getElementById('medication-tbody-' + questionId);
        const row = tbody.querySelector('tr').cloneNode(true);
        const inputs = row.querySelectorAll('input');
        inputs.forEach(input => {
            input.name = input.name.replace(/\[0\]/, `[${medicationRowCounters[questionId]}]`);
            input.value = '';
        });
        tbody.appendChild(row);
        medicationRowCounters[questionId]++;
    }

    // Show/hide deductible amount field
    document.querySelectorAll('input[name="has_deductible"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const field = document.getElementById('deductible-amount-field');
            field.style.display = this.value === '1' ? 'block' : 'none';
        });
    });

    // Show/hide telemedicine details
    document.querySelectorAll('input[name="telemedicine_only"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Add any telemedicine-specific logic here
        });
    });

    // Handle dynamic medical questions - show/hide additional info fields
    document.querySelectorAll('.question-response').forEach(input => {
        input.addEventListener('change', function() {
            const questionId = this.getAttribute('data-question-id');
            const additionalInfoField = document.getElementById('additional-info-' + questionId);
            
            if (additionalInfoField) {
                // Check if this question requires additional info and response is 'yes'
                const isYes = this.value === 'yes' || this.value !== '';
                additionalInfoField.style.display = isYes ? 'block' : 'none';
            }
        });
    });

    // Before form submission, convert medication tables to JSON
    document.querySelector('form')?.addEventListener('submit', function(e) {
        // Find all medication tables and convert to JSON
        document.querySelectorAll('[id^="medication-tbody-"]').forEach(tbody => {
            const questionId = tbody.id.replace('medication-tbody-', '');
            const rows = tbody.querySelectorAll('tr');
            const medications = [];
            
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                if (inputs.length >= 4) {
                    medications.push({
                        applicant_name: inputs[0].value,
                        medication: inputs[1].value,
                        diagnosis: inputs[2].value,
                        date_started: inputs[3].value
                    });
                }
            });
            
            // Store as JSON in hidden field or update the additional_info field
            const additionalInfoField = document.querySelector(`input[name="medical_questions[${questionId}][additional_info]"], textarea[name="medical_questions[${questionId}][additional_info]"]`);
            if (additionalInfoField && medications.length > 0) {
                // Create hidden input to store JSON
                let hiddenInput = document.querySelector(`input[name="medical_questions[${questionId}][additional_info_json]"]`);
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `medical_questions[${questionId}][additional_info]`;
                    additionalInfoField.parentNode.appendChild(hiddenInput);
                }
                hiddenInput.value = JSON.stringify(medications);
            }
        });
    });

    // Enable/disable benefit checkboxes based on plan selection
    function updateBenefitCheckboxes() {
        const selectedPlan = document.querySelector('input[name="plan_id"]:checked');
        const allCheckboxes = document.querySelectorAll('.benefit-checkbox');
        
        if (selectedPlan) {
            const planId = selectedPlan.value;
            allCheckboxes.forEach(checkbox => {
                const checkboxPlanId = checkbox.getAttribute('data-plan');
                if (checkboxPlanId === planId) {
                    checkbox.disabled = false;
                    // Inpatient is always required and checked
                    if (checkbox.classList.contains('inpatient-checkbox')) {
                        checkbox.checked = true;
                        checkbox.required = true;
                    }
                } else {
                    checkbox.disabled = true;
                    checkbox.checked = false;
                }
            });
        } else {
            allCheckboxes.forEach(checkbox => {
                checkbox.disabled = true;
            });
        }
    }

    // Handle Optical/Dental requirement (must be selected together)
    document.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const isOptical = this.getAttribute('data-optical') === '1';
            const isDental = this.getAttribute('data-dental') === '1';
            const planId = this.getAttribute('data-plan');
            
            if (isOptical || isDental) {
                // Find the other (Optical or Dental) checkbox for the same plan
                const otherCheckbox = document.querySelector(
                    `.benefit-checkbox[data-plan="${planId}"][data-${isOptical ? 'dental' : 'optical'}="1"]`
                );
                
                if (otherCheckbox && this.checked) {
                    // If one is checked, check the other
                    otherCheckbox.checked = true;
                } else if (otherCheckbox && !this.checked) {
                    // If one is unchecked, uncheck the other
                    otherCheckbox.checked = false;
                }
            }
        });
    });

    // Highlight selected plan row and enable/disable checkboxes
    document.querySelectorAll('input[name="plan_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove highlight from all rows
            document.querySelectorAll('tbody tr').forEach(row => {
                row.classList.remove('bg-blue-100', 'border-blue-500');
            });
            
            // Highlight selected row
            if (this.checked) {
                const row = this.closest('tr');
                row.classList.add('bg-blue-100', 'border-blue-500');
            }
            
            // Update benefit checkboxes
            updateBenefitCheckboxes();
            
            // Update benefit checkboxes
            updateBenefitCheckboxes();
        });
        
        // Highlight initially selected plan
        if (radio.checked) {
            const row = radio.closest('tr');
            row.classList.add('bg-blue-100', 'border-blue-500');
        }
    });
    
    // Initialize checkboxes on page load
    updateBenefitCheckboxes();
    
    // Premium calculation function
    function calculatePremium() {
        const selectedPlan = document.querySelector('input[name="plan_id"]:checked');
        const premiumCalcDiv = document.getElementById('premium-calculation');
        
        if (!selectedPlan) {
            premiumCalcDiv.style.display = 'none';
            return;
        }
        
        premiumCalcDiv.style.display = 'block';
        
        const planId = selectedPlan.value;
        const numberOfDependents = parseInt(document.getElementById('number_of_dependents').value) || 0;
        
        // Get all checked benefit checkboxes for the selected plan
        const checkedBenefits = document.querySelectorAll(
            `.benefit-checkbox[data-plan="${planId}"]:checked`
        );
        
        // Calculate base premium from selected benefits
        let basePremium = 0;
        checkedBenefits.forEach(checkbox => {
            const benefitAmount = parseFloat(checkbox.value) || 0;
            basePremium += benefitAmount;
        });
        
        // Calculate dependents premium (typically 50% of base premium per dependent, adjust as needed)
        // You can modify this multiplier based on your business rules
        const dependentMultiplier = 0.5; // 50% of base premium per dependent
        const dependentsPremium = basePremium * dependentMultiplier * numberOfDependents;
        
        // Calculate subtotal
        const subtotalPremium = basePremium + dependentsPremium;
        
        // Calculate insurance training levy (0.5% of subtotal)
        const trainingLevy = subtotalPremium * 0.005;
        
        // Stamp duty (fixed)
        const stampDuty = 35000;
        
        // Calculate total premium due
        const totalPremiumDue = subtotalPremium + trainingLevy + stampDuty;
        
        // Update display
        document.getElementById('base-premium').textContent = formatCurrency(basePremium);
        document.getElementById('dependents-count').textContent = numberOfDependents;
        document.getElementById('dependents-premium').textContent = formatCurrency(dependentsPremium);
        document.getElementById('subtotal-premium').textContent = formatCurrency(subtotalPremium);
        document.getElementById('training-levy').textContent = formatCurrency(trainingLevy);
        document.getElementById('stamp-duty').textContent = formatCurrency(stampDuty);
        document.getElementById('total-premium-due').textContent = formatCurrency(totalPremiumDue);
    }
    
    // Format currency
    function formatCurrency(amount) {
        return 'UGX ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Add event listeners for premium calculation
    document.querySelectorAll('input[name="plan_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            calculatePremium();
        });
    });
    
    document.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            calculatePremium();
        });
    });
    
    // Calculate on page load if plan is already selected
    if (document.querySelector('input[name="plan_id"]:checked')) {
        calculatePremium();
    }
</script>
