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
        
        <!-- Deductible Option -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">WOULD YOU LIKE A DEDUCTIBLE? PLEASE TICK APPLICABLE CHOICE:</label>
            <div class="flex gap-6">
                <label class="flex items-center">
                    <input type="radio" name="has_deductible" value="1" {{ old('has_deductible', $client->has_deductible ?? false) ? 'checked' : '' }} class="mr-2">
                    <span>YES</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="has_deductible" value="0" {{ !old('has_deductible', $client->has_deductible ?? false) ? 'checked' : '' }} class="mr-2">
                    <span>NO</span>
                </label>
            </div>
            <p class="text-xs text-slate-600 mt-2">If you opt for a deductible, you have agreed to incur a cumulative ugx 100,000 self-insurance per person on outpatient claims before claiming from the outpatient benefit limit.</p>
            <div id="deductible-amount-field" class="mt-4" style="display: {{ old('has_deductible', $client->has_deductible ?? false) ? 'block' : 'none' }};">
                <label for="deductible_amount" class="block text-sm font-medium text-slate-700 mb-1">Deductible Amount (UGX)</label>
                <input type="number" name="deductible_amount" id="deductible_amount" value="{{ old('deductible_amount', $client->deductible_amount ?? 100000) }}" placeholder="Enter deductible amount" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                            <tr class="hover:bg-blue-50 transition-colors {{ $isSelected ? 'bg-blue-100' : '' }}">
                                <td class="border border-slate-300 px-4 py-3 bg-slate-50">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="plan_id" value="{{ $plan->id }}" id="plan_{{ $plan->id }}" 
                                               {{ $isSelected ? 'checked' : '' }} 
                                               required
                                               class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300">
                                        <span class="font-bold text-slate-900 text-base">{{ $plan->name }}</span>
                                    </label>
                                </td>
                                @foreach($serviceCategories as $category)
                                    @php
                                        $pivot = $planCategories->get($category->name);
                                        $benefitAmount = $pivot ? ($pivot->pivot->benefit_amount ?? 0) : 0;
                                    @endphp
                                    <td class="border border-slate-300 px-3 py-3 text-center font-medium">
                                        @if($benefitAmount > 0)
                                            {{ number_format($benefitAmount, 0, '.', ',') }}
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
    </div>

    <!-- CONFIDENTIAL MEDICAL HISTORY Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">CONFIDENTIAL MEDICAL HISTORY</h2>
        <p class="text-sm text-slate-600 mb-4">State whether you as the principal member or any of your listed dependants have ever been treated or are currently receiving medical treatment, or expect to receive medical treatment for any of the following illnesses including but not limited to:</p>
        
        <div class="space-y-4">
            @php
                $medicalHistoryQuestions = [
                    'Respiratory ailments e.g. tuberculosis, persistent cough, allergies, cigarette smoking related disorders, shortness of breath, asthma',
                    'Have you or any of your dependants ever sought counseling or treatment in connection with HIV or AIDS infections or tested positive for HIV or AIDS?',
                    'Ear, nose and throat disorders e.g. hearing/speech impairment, ear infections, sinus problems, nasal/throat surgery, tonsils, adenoids, previous nasal injuries, upper airway infections, epistaxis',
                    'Do you or any of your dependants have any hereditary disorders, birth defects or congenital conditions?',
                    'Cardiovascular (heart and blood vessels) disorders e.g. high blood pressure, hypertension, varicose veins, palpitations, deep vein thrombosis, low blood pressure',
                    'Have you or any of your dependants ever sought counseling or treatment in connection with sexual transmitted infection e.g. gonorrhoea, syphilis, herpes simplex, Chlamydia',
                    'Have you ever had any endoscopic study of the oesophagus, stomach or Colon and/or treatment and diagnosis of gastro-intestinal disorders e.g. recurrent indigestion, heartburn, ulcers, hernia, piles and fissures?',
                    'Musculo-skeletal disorders e.g. arthritis, Back problems, gout, and osteoporosis. All joint problems and fractures',
                    'Neurological disorders e.g. epilepsy, Stroke. Brain or spinal cord disorders, Headache, migraine, Paralysis, meningitis',
                    'Do you or any of your dependants have incomplete dental treatment plan, dental implants, orthodontic treatment, dentures, braces and wisdom teeth problems or do you or any of your dependants currently receive, or expect to receive dental treatment in the next 12 months?',
                    'Psychological disorders e.g. alcohol or drug dependency, anxiety disorder, insomnia, depression, stress, attention deficit disorder, post-traumatic stress, attempted suicide, bipolar disorder',
                    'State whether you or any of your dependants have received medical advice or treatment for any tropical disease e.g. leprosy, sleeping sickness, elephantiasis, bilharzia, yellow fever',
                    'Gynecological and obstetrical disorders e.g. Fibroids, ectopic pregnancy, caesarian section, Menstrual irregularities. Abnormal pap smear, receiving hormone treatment. Uterine bleeding, Laparoscopic surgery, Dilatation and curettage, miscarriages, pregnancy related problems.',
                    'Pregnant, if positive, provide expected date of delivery (dd/mm/yy)',
                    'Respiratory disorders e.g. asthma, rhinitis, chronic bronchitis, cigarette smoking related disorders, tuberculosis, persistent cough, allergies, chronic obstruction pulmonary disease, shortness of breath.',
                    'Endocrine disorders e.g. diabetes, high cholesterol, thyroid abnormalities',
                    'Skin disorders e.g. eczema, melanoma, skin cancer, burns, scars, keloids',
                    'Genital-urinary system e.g. Pelvic inflammatory disease prostate problem, abnormalities of the penis, scrotum. Reproductive system, blood in the urine, kidney stones, kidney failure, bladder problems, Dialysis.',
                    'Investigations and/or specialized treatment: In and out of hospital',
                    'Cancer, growths or tumors whether benign or malignant',
                    'Eye related disorders e.g. blindness, glaucoma, eye surgery, cataracts, lens implants, refractive and laser surgery',
                    'Are you or any of your dependants on regular medication? If your response is "YES", please indicate the details as required below:',
                ];
            @endphp

            @foreach($medicalHistoryQuestions as $index => $question)
                <div class="border border-slate-200 rounded-lg p-4 bg-white">
                    <p class="text-sm font-medium text-slate-700 mb-3">{{ $index + 1 }}. {{ $question }}</p>
                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="radio" name="medical_history[{{ $index }}]" value="yes" class="mr-2">
                            <span>YES</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="medical_history[{{ $index }}]" value="no" checked class="mr-2">
                            <span>NO</span>
                        </label>
                    </div>
                    @if($index == 13) <!-- Pregnancy question -->
                        <div class="mt-3" id="pregnancy-details" style="display: none;">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Expected Date of Delivery</label>
                            <input type="date" name="pregnancy_expected_date" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    @endif
                    @if($index == 21) <!-- Regular medication question -->
                        <div class="mt-4" id="medication-details" style="display: none;">
                            <table class="w-full border border-slate-300">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 text-left text-sm">Applicant Name</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left text-sm">Prescribed Medication</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left text-sm">Diagnosis</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left text-sm">Date Started/To Be Started</th>
                                    </tr>
                                </thead>
                                <tbody id="medication-tbody">
                                    <tr>
                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[0][applicant_name]" placeholder="Enter applicant name" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[0][medication]" placeholder="Enter medication" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                        <td class="border border-slate-300 px-3 py-2"><input type="text" name="medications[0][diagnosis]" placeholder="Enter diagnosis" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                        <td class="border border-slate-300 px-3 py-2"><input type="date" name="medications[0][date_started]" class="w-full px-2 py-1 border border-slate-300 rounded"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" onclick="addMedicationRow()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Add Medication</button>
                        </div>
                    @endif
                </div>
            @endforeach
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

    // Add medication row
    function addMedicationRow() {
        const tbody = document.getElementById('medication-tbody');
        const row = tbody.querySelector('tr').cloneNode(true);
        const inputs = row.querySelectorAll('input');
        inputs.forEach(input => {
            input.name = input.name.replace(/\[0\]/, `[${medicationCount}]`);
            input.value = '';
        });
        tbody.appendChild(row);
        medicationCount++;
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

    // Show/hide pregnancy details
    document.querySelectorAll('input[name="medical_history[13]"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const field = document.getElementById('pregnancy-details');
            field.style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    // Show/hide medication details
    document.querySelectorAll('input[name="medical_history[21]"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const field = document.getElementById('medication-details');
            field.style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    // Highlight selected plan row
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
        });
        
        // Highlight initially selected plan
        if (radio.checked) {
            const row = radio.closest('tr');
            row.classList.add('bg-blue-100', 'border-blue-500');
        }
    });
</script>
