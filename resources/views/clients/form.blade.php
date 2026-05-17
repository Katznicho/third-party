<form action="{{ $action }}" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-8" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <!-- Error and Success Messages -->
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-red-800 mb-1">Error</h4>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-green-800 mb-1">Success</h4>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm text-red-700">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
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
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">
                    Title
                    @if(isset($requiredFields) && in_array('title', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <select 
                    name="title" 
                    id="title" 
                    @if(isset($requiredFields) && in_array('title', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-300 @enderror"
                >
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
                <label for="surname" class="block text-sm font-medium text-slate-700 mb-1">
                    Surname
                    @if(isset($requiredFields) && in_array('surname', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="surname" 
                    id="surname" 
                    value="{{ old('surname', $client->surname ?? '') }}" 
                    placeholder="Enter surname in BLOCK letters" 
                    @if(isset($requiredFields) && in_array('surname', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase @error('surname') border-red-300 @enderror" 
                    style="text-transform: uppercase;"
                >
                @error('surname')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">
                    First Name <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="first_name" 
                    id="first_name" 
                    value="{{ old('first_name', $client->first_name ?? '') }}" 
                    placeholder="Enter first name in BLOCK letters" 
                    required 
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase @error('first_name') border-red-300 @enderror" 
                    style="text-transform: uppercase;"
                >
                @error('first_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Other Names -->
            <div>
                <label for="other_names" class="block text-sm font-medium text-slate-700 mb-1">
                    Other Names
                    @if(isset($requiredFields) && in_array('other_names', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="other_names" 
                    id="other_names" 
                    value="{{ old('other_names', $client->other_names ?? '') }}" 
                    placeholder="Enter other names in BLOCK letters" 
                    @if(isset($requiredFields) && in_array('other_names', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase @error('other_names') border-red-300 @enderror" 
                    style="text-transform: uppercase;"
                >
                @error('other_names')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ID/Passport Number -->
            <div>
                <label for="id_passport_no" class="block text-sm font-medium text-slate-700 mb-1">
                    ID / Passport No. <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="id_passport_no" 
                    id="id_passport_no" 
                    value="{{ old('id_passport_no', $client->id_passport_no ?? '') }}" 
                    placeholder="Enter ID or passport number" 
                    required 
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('id_passport_no') border-red-300 @enderror"
                >
                @error('id_passport_no')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Gender -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Gender
                    @if(isset($requiredFields) && in_array('gender', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="gender" 
                            value="Male" 
                            {{ old('gender', $client->gender ?? '') === 'Male' ? 'checked' : '' }}
                            @if(isset($requiredFields) && in_array('gender', $requiredFields)) required @endif
                            class="mr-2 @error('gender') border-red-300 @enderror"
                        >
                        <span>Male</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="gender" 
                            value="Female" 
                            {{ old('gender', $client->gender ?? '') === 'Female' ? 'checked' : '' }}
                            @if(isset($requiredFields) && in_array('gender', $requiredFields)) required @endif
                            class="mr-2 @error('gender') border-red-300 @enderror"
                        >
                        <span>Female</span>
                    </label>
                </div>
                @error('gender')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- TIN -->
            <div>
                <label for="tin" class="block text-sm font-medium text-slate-700 mb-1">
                    TIN
                    @if(isset($requiredFields) && in_array('tin', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="tin" 
                    id="tin" 
                    value="{{ old('tin', $client->tin ?? '') }}" 
                    placeholder="Enter TIN number" 
                    @if(isset($requiredFields) && in_array('tin', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tin') border-red-300 @enderror"
                >
                @error('tin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date of Birth -->
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">
                    Date of Birth
                    @if(isset($requiredFields) && in_array('date_of_birth', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="date" 
                    name="date_of_birth" 
                    id="date_of_birth" 
                    value="{{ old('date_of_birth', $client->date_of_birth ? $client->date_of_birth->format('Y-m-d') : '') }}" 
                    @if(isset($requiredFields) && in_array('date_of_birth', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date_of_birth') border-red-300 @enderror"
                >
                @error('date_of_birth')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Marital Status -->
            <div>
                <label for="marital_status" class="block text-sm font-medium text-slate-700 mb-1">
                    Marital Status
                    @if(isset($requiredFields) && in_array('marital_status', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <select 
                    name="marital_status" 
                    id="marital_status" 
                    @if(isset($requiredFields) && in_array('marital_status', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('marital_status') border-red-300 @enderror"
                >
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

            <!-- Height -->
            <div>
                <label for="height" class="block text-sm font-medium text-slate-700 mb-1">
                    Height (ft & inches)
                    @if(isset($requiredFields) && in_array('height', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="height" 
                    id="height" 
                    value="{{ old('height', $client->height ?? '') }}" 
                    placeholder="e.g., 5'10" 
                    @if(isset($requiredFields) && in_array('height', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('height') border-red-300 @enderror"
                >
                @error('height')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weight -->
            <div>
                <label for="weight" class="block text-sm font-medium text-slate-700 mb-1">
                    Weight (Kgs)
                    @if(isset($requiredFields) && in_array('weight', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="weight" 
                    id="weight" 
                    value="{{ old('weight', $client->weight ?? '') }}" 
                    placeholder="e.g., 70" 
                    @if(isset($requiredFields) && in_array('weight', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('weight') border-red-300 @enderror"
                >
                @error('weight')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Employer Name -->
            <div>
                <label for="employer_name" class="block text-sm font-medium text-slate-700 mb-1">
                    Name of Employer (if employed)
                    @if(isset($requiredFields) && in_array('employer_name', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="employer_name" 
                    id="employer_name" 
                    value="{{ old('employer_name', $client->employer_name ?? '') }}" 
                    placeholder="Enter employer name" 
                    @if(isset($requiredFields) && in_array('employer_name', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employer_name') border-red-300 @enderror"
                >
                @error('employer_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Occupation -->
            <div>
                <label for="occupation" class="block text-sm font-medium text-slate-700 mb-1">
                    Occupation
                    @if(isset($requiredFields) && in_array('occupation', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="occupation" 
                    id="occupation" 
                    value="{{ old('occupation', $client->occupation ?? '') }}" 
                    placeholder="Enter occupation" 
                    @if(isset($requiredFields) && in_array('occupation', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('occupation') border-red-300 @enderror"
                >
                @error('occupation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nationality -->
            <div>
                <label for="nationality" class="block text-sm font-medium text-slate-700 mb-1">
                    Nationality
                    @if(isset($requiredFields) && in_array('nationality', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <select 
                    name="nationality" 
                    id="nationality" 
                    @if(isset($requiredFields) && in_array('nationality', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nationality') border-red-300 @enderror"
                >
                    <option value="">-- Select Nationality --</option>
                    @foreach($countries as $code => $country)
                        <option value="{{ $code }}" {{ old('nationality', $client->nationality ?? '') === $code ? 'selected' : '' }}>
                            {{ $country }}
                        </option>
                    @endforeach
                </select>
                @error('nationality')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- CONTACT DETAILS Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">CONTACT DETAILS</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Home Physical Address -->
            <div>
                <label for="home_physical_address" class="block text-sm font-medium text-slate-700 mb-1">
                    Home Physical Address
                    @if(isset($requiredFields) && in_array('home_physical_address', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <textarea 
                    name="home_physical_address" 
                    id="home_physical_address" 
                    rows="2" 
                    placeholder="Enter home physical address" 
                    @if(isset($requiredFields) && in_array('home_physical_address', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('home_physical_address') border-red-300 @enderror"
                >{{ old('home_physical_address', $client->home_physical_address ?? '') }}</textarea>
                @error('home_physical_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Office Physical Address -->
            <div>
                <label for="office_physical_address" class="block text-sm font-medium text-slate-700 mb-1">
                    Office Physical Address
                    @if(isset($requiredFields) && in_array('office_physical_address', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <textarea 
                    name="office_physical_address" 
                    id="office_physical_address" 
                    rows="2" 
                    placeholder="Enter office physical address" 
                    @if(isset($requiredFields) && in_array('office_physical_address', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('office_physical_address') border-red-300 @enderror"
                >{{ old('office_physical_address', $client->office_physical_address ?? '') }}</textarea>
                @error('office_physical_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Home Telephone -->
            <div>
                <label for="home_telephone" class="block text-sm font-medium text-slate-700 mb-1">
                    Home Telephone
                    @if(isset($requiredFields) && in_array('home_telephone', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="home_telephone" 
                    id="home_telephone" 
                    value="{{ old('home_telephone', $client->home_telephone ?? '') }}" 
                    placeholder="Enter home telephone" 
                    @if(isset($requiredFields) && in_array('home_telephone', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('home_telephone') border-red-300 @enderror"
                >
                @error('home_telephone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Office Telephone -->
            <div>
                <label for="office_telephone" class="block text-sm font-medium text-slate-700 mb-1">
                    Office Telephone
                    @if(isset($requiredFields) && in_array('office_telephone', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="office_telephone" 
                    id="office_telephone" 
                    value="{{ old('office_telephone', $client->office_telephone ?? '') }}" 
                    placeholder="Enter office telephone" 
                    @if(isset($requiredFields) && in_array('office_telephone', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('office_telephone') border-red-300 @enderror"
                >
                @error('office_telephone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Payment Phone (Mobile Money) -->
            <div>
                <label for="cell_phone" class="block text-sm font-medium text-slate-700 mb-1">
                    Payment Phone (Mobile Money)
                    @if(isset($requiredFields) && in_array('cell_phone', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="cell_phone" 
                    id="cell_phone" 
                    value="{{ old('cell_phone', $client->cell_phone ?? '') }}" 
                    placeholder="Enter mobile money phone number (e.g. 2567XXXXXXXX)" 
                    @if(isset($requiredFields) && in_array('cell_phone', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cell_phone') border-red-300 @enderror"
                >
                <p class="mt-1 text-xs text-slate-500">
                    This number will be used to send Yo Payments mobile money prompts for premium and other payments.
                </p>
                @error('cell_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- WhatsApp Line -->
            <div>
                <label for="whatsapp_line" class="block text-sm font-medium text-slate-700 mb-1">
                    WhatsApp Line
                    @if(isset($requiredFields) && in_array('whatsapp_line', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="whatsapp_line" 
                    id="whatsapp_line" 
                    value="{{ old('whatsapp_line', $client->whatsapp_line ?? '') }}" 
                    placeholder="Enter WhatsApp number" 
                    @if(isset($requiredFields) && in_array('whatsapp_line', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('whatsapp_line') border-red-300 @enderror"
                >
                @error('whatsapp_line')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                    Email
                    @if(isset($requiredFields) && in_array('email', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    value="{{ old('email', $client->email ?? '') }}" 
                    placeholder="Enter email address" 
                    @if(isset($requiredFields) && in_array('email', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-300 @enderror"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- NEXT OF KIN DETAILS Section -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">NEXT OF KIN DETAILS</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Next of Kin Title -->
            <div>
                <label for="next_of_kin_title" class="block text-sm font-medium text-slate-700 mb-1">
                    Title
                    @if(isset($requiredFields) && in_array('next_of_kin_title', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <select 
                    name="next_of_kin_title" 
                    id="next_of_kin_title" 
                    @if(isset($requiredFields) && in_array('next_of_kin_title', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_title') border-red-300 @enderror"
                >
                    <option value="">Select Title</option>
                    <option value="Mr" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Mr' ? 'selected' : '' }}>Mr</option>
                    <option value="Mrs" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                    <option value="Miss" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Miss' ? 'selected' : '' }}>Miss</option>
                    <option value="Dr" {{ old('next_of_kin_title', $client->next_of_kin_title ?? '') === 'Dr' ? 'selected' : '' }}>Dr</option>
                </select>
                @error('next_of_kin_title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Surname -->
            <div>
                <label for="next_of_kin_surname" class="block text-sm font-medium text-slate-700 mb-1">
                    Surname
                    @if(isset($requiredFields) && in_array('next_of_kin_surname', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_surname" 
                    id="next_of_kin_surname" 
                    value="{{ old('next_of_kin_surname', $client->next_of_kin_surname ?? '') }}" 
                    placeholder="Enter next of kin surname" 
                    @if(isset($requiredFields) && in_array('next_of_kin_surname', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_surname') border-red-300 @enderror"
                >
                @error('next_of_kin_surname')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin First Name -->
            <div>
                <label for="next_of_kin_first_name" class="block text-sm font-medium text-slate-700 mb-1">
                    First Name
                    @if(isset($requiredFields) && in_array('next_of_kin_first_name', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_first_name" 
                    id="next_of_kin_first_name" 
                    value="{{ old('next_of_kin_first_name', $client->next_of_kin_first_name ?? '') }}" 
                    placeholder="Enter next of kin first name" 
                    @if(isset($requiredFields) && in_array('next_of_kin_first_name', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_first_name') border-red-300 @enderror"
                >
                @error('next_of_kin_first_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Other Names -->
            <div>
                <label for="next_of_kin_other_names" class="block text-sm font-medium text-slate-700 mb-1">
                    Other Names
                    @if(isset($requiredFields) && in_array('next_of_kin_other_names', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_other_names" 
                    id="next_of_kin_other_names" 
                    value="{{ old('next_of_kin_other_names', $client->next_of_kin_other_names ?? '') }}" 
                    placeholder="Enter next of kin other names" 
                    @if(isset($requiredFields) && in_array('next_of_kin_other_names', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_other_names') border-red-300 @enderror"
                >
                @error('next_of_kin_other_names')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Relation -->
            <div>
                <label for="next_of_kin_relation" class="block text-sm font-medium text-slate-700 mb-1">
                    Relation to Principal Member
                    @if(isset($requiredFields) && in_array('next_of_kin_relation', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_relation" 
                    id="next_of_kin_relation" 
                    value="{{ old('next_of_kin_relation', $client->next_of_kin_relation ?? '') }}" 
                    placeholder="e.g., Spouse, Parent, Sibling" 
                    @if(isset($requiredFields) && in_array('next_of_kin_relation', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_relation') border-red-300 @enderror"
                >
                @error('next_of_kin_relation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin ID/Passport -->
            <div>
                <label for="next_of_kin_id_passport_no" class="block text-sm font-medium text-slate-700 mb-1">
                    Passport / ID No.
                    @if(isset($requiredFields) && in_array('next_of_kin_id_passport_no', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_id_passport_no" 
                    id="next_of_kin_id_passport_no" 
                    value="{{ old('next_of_kin_id_passport_no', $client->next_of_kin_id_passport_no ?? '') }}" 
                    placeholder="Enter next of kin ID or passport number" 
                    @if(isset($requiredFields) && in_array('next_of_kin_id_passport_no', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_id_passport_no') border-red-300 @enderror"
                >
                @error('next_of_kin_id_passport_no')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Cell Phone -->
            <div>
                <label for="next_of_kin_cell_phone" class="block text-sm font-medium text-slate-700 mb-1">
                    Cell Phone
                    @if(isset($requiredFields) && in_array('next_of_kin_cell_phone', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="text" 
                    name="next_of_kin_cell_phone" 
                    id="next_of_kin_cell_phone" 
                    value="{{ old('next_of_kin_cell_phone', $client->next_of_kin_cell_phone ?? '') }}" 
                    placeholder="Enter next of kin cell phone" 
                    @if(isset($requiredFields) && in_array('next_of_kin_cell_phone', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_cell_phone') border-red-300 @enderror"
                >
                @error('next_of_kin_cell_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Email -->
            <div>
                <label for="next_of_kin_email" class="block text-sm font-medium text-slate-700 mb-1">
                    Email
                    @if(isset($requiredFields) && in_array('next_of_kin_email', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <input 
                    type="email" 
                    name="next_of_kin_email" 
                    id="next_of_kin_email" 
                    value="{{ old('next_of_kin_email', $client->next_of_kin_email ?? '') }}" 
                    placeholder="Enter next of kin email address" 
                    @if(isset($requiredFields) && in_array('next_of_kin_email', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_email') border-red-300 @enderror"
                >
                @error('next_of_kin_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Post Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_post_address" class="block text-sm font-medium text-slate-700 mb-1">
                    Post Address
                    @if(isset($requiredFields) && in_array('next_of_kin_post_address', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <textarea 
                    name="next_of_kin_post_address" 
                    id="next_of_kin_post_address" 
                    rows="2" 
                    placeholder="Enter next of kin postal address" 
                    @if(isset($requiredFields) && in_array('next_of_kin_post_address', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_post_address') border-red-300 @enderror"
                >{{ old('next_of_kin_post_address', $client->next_of_kin_post_address ?? '') }}</textarea>
                @error('next_of_kin_post_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Next of Kin Physical Address -->
            <div class="md:col-span-2">
                <label for="next_of_kin_physical_address" class="block text-sm font-medium text-slate-700 mb-1">
                    Physical Address
                    @if(isset($requiredFields) && in_array('next_of_kin_physical_address', $requiredFields))
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <textarea 
                    name="next_of_kin_physical_address" 
                    id="next_of_kin_physical_address" 
                    rows="2" 
                    placeholder="Enter next of kin physical address" 
                    @if(isset($requiredFields) && in_array('next_of_kin_physical_address', $requiredFields)) required @endif
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('next_of_kin_physical_address') border-red-300 @enderror"
                >{{ old('next_of_kin_physical_address', $client->next_of_kin_physical_address ?? '') }}</textarea>
                @error('next_of_kin_physical_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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

            <!-- Percentage payable by insurance -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-sm font-medium text-slate-700 mb-2">Percentage payable by insurance</label>
                <p class="text-xs text-slate-600 mb-3">
                    Percentage of each eligible invoice that is paid by the insurance company.
                    Default is 100% (insurance pays everything except deductible / copay / coinsurance).
                </p>
                <div>
                    <label for="insurance_payable_percentage" class="block text-sm font-medium text-slate-700 mb-1">
                        Percentage payable by insurance (%)
                    </label>
                    <input
                        type="number"
                        name="insurance_payable_percentage"
                        id="insurance_payable_percentage"
                        value="{{ old('insurance_payable_percentage', isset($client) ? ($client->insurance_payable_percentage ?? 100) : 100) }}"
                        placeholder="Enter percentage payable by insurance"
                        step="0.01"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>

            <!-- Grace period and active period -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-sm font-medium text-slate-700 mb-2">Grace and active periods</label>
                <p class="text-xs text-slate-600 mb-3">
                    Configure how long the client can stay in <strong>pending payment</strong> before the account can be frozen/suspended,
                    and how long the cover stays <strong>active</strong> after payment is received.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="premium_grace_days" class="block text-sm font-medium text-slate-700 mb-1">
                            Grace period (days) before freezing/suspension
                        </label>
                        <input
                            type="number"
                            name="premium_grace_days"
                            id="premium_grace_days"
                            value="{{ old('premium_grace_days', isset($client) ? $client->premium_grace_days : null) }}"
                            placeholder="e.g. 30"
                            min="0"
                            max="365"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-xs text-slate-500 mt-1">
                            If empty, the insurance company's default grace period per payment method is used.
                        </p>
                    </div>
                    <div>
                        <label for="active_period_days" class="block text-sm font-medium text-slate-700 mb-1">
                            Active period
                        </label>
                        <input
                            type="number"
                            name="active_period_days"
                            id="active_period_days"
                            value="{{ old('active_period_days', isset($client) ? $client->active_period_days : null) }}"
                            placeholder="e.g. 365"
                            min="0"
                            max="3650"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-xs text-slate-500 mt-1">
                            If empty, the policy inception and expiry dates determine the active period.
                        </p>
                    </div>
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
                    
                    @php
                        $insuranceCompany = auth()->user()->insuranceCompany;
                        $defaultCopayContributes = $insuranceCompany ? $insuranceCompany->copay_contributes_to_deductible : false;
                        $defaultCoinsuranceContributes = $insuranceCompany ? $insuranceCompany->coinsurance_contributes_to_deductible : false;
                        $policy = isset($client) && $client->policies->isNotEmpty() ? $client->policies->first() : null;
                        $copayContributes = old('copay_contributes_to_deductible', $policy ? $policy->copay_contributes_to_deductible : null) ?? $defaultCopayContributes;
                        $coinsuranceContributes = old('coinsurance_contributes_to_deductible', $policy ? $policy->coinsurance_contributes_to_deductible : null) ?? $defaultCoinsuranceContributes;
                    @endphp
                    
                    <!-- Contribution Flags -->
                    <div class="mt-4 space-y-3 pt-4 border-t border-slate-200">
                        <p class="text-sm font-medium text-slate-700 mb-2">Deductible Contribution Settings:</p>
                        <p class="text-xs text-slate-500 mb-3">Configure whether copay and coinsurance payments count towards meeting the deductible.</p>
                        
                        <div class="space-y-2">
                            <label class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="copay_contributes_to_deductible" 
                                    id="copay_contributes_to_deductible" 
                                    value="1"
                                    {{ $copayContributes ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-0.5"
                                >
                                <span class="ml-2 text-sm text-slate-700">
                                    Copay contributes to deductible
                                    <span class="text-xs text-slate-500 block mt-0.5">Copay amounts will count towards meeting the deductible</span>
                                </span>
                            </label>
                            
                            <label class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="coinsurance_contributes_to_deductible" 
                                    id="coinsurance_contributes_to_deductible" 
                                    value="1"
                                    {{ $coinsuranceContributes ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-0.5"
                                >
                                <span class="ml-2 text-sm text-slate-700">
                                    Coinsurance contributes to deductible
                                    <span class="text-xs text-slate-500 block mt-0.5">Coinsurance amounts will count towards meeting the deductible</span>
                                </span>
                            </label>
                        </div>
                    </div>
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
                
                // Prepare plan data for JavaScript (ensure numeric values are floats)
                $plansData = $plans->map(function($plan) {
                    return [
                        'id' => (int)$plan->id,
                        'dependent_coverage_multiplier' => (float)($plan->dependent_coverage_multiplier ?? 0.50),
                        'dependent_multiplier_tiers' => $plan->dependent_multiplier_tiers ?? null,
                        'dependent_multiplier_floor' => $plan->dependent_multiplier_floor !== null ? (float)$plan->dependent_multiplier_floor : null,
                        'insurance_training_levy_percentage' => (float)($plan->insurance_training_levy_percentage ?? 0.50),
                        'stamp_duty_amount' => (float)($plan->stamp_duty_amount ?? 35000),
                        'premium_calculation_method' => $plan->premium_calculation_method ?? 'benefit_based',
                        'base_premium' => (float)($plan->base_premium ?? 0),
                    ];
                })->keyBy('id');
                
                // Prepare medical questions data for JavaScript
                $medicalQuestionsData = isset($medicalQuestions) ? $medicalQuestions->map(function($question) {
                    return [
                        'id' => $question->id,
                        'question_type' => $question->question_type,
                        'has_monetary_impact' => $question->has_monetary_impact ?? false,
                        'monetary_impact_type' => $question->monetary_impact_type ?? 'none',
                        'monetary_impact_amount' => $question->monetary_impact_amount ?? 0,
                        'monetary_impact_is_percentage' => $question->monetary_impact_is_percentage ?? false,
                        'monetary_impact_applies_to_response' => strtolower(trim($question->monetary_impact_applies_to_response ?? 'yes')),
                        'monetary_impact_description' => $question->monetary_impact_description ?? '',
                    ];
                })->keyBy('id') : [];
            @endphp
            
            <div class="overflow-x-auto border border-slate-300 rounded-lg">
                <table class="w-full text-sm bg-white">
                    <thead class="bg-slate-200">
                        <tr>
                            <th class="border border-slate-300 px-4 py-3 text-left font-bold text-slate-900">PLAN NAME</th>
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
                                        $baseAmount = $pivot ? ($pivot->pivot->base_amount ?? 0) : 0;
                                        $isInpatient = $category->name === 'Inpatient';
                                        $isOptical = $category->name === 'Optical';
                                        $isDental = $category->name === 'Dental';
                                        $oldSelected = old('selected_benefits.' . $plan->id . '.' . $category->id, false);
                                    @endphp
                                    <td class="border border-slate-300 px-3 py-3 text-center font-medium">
                                        @if($baseAmount > 0 || $benefitAmount > 0)
                                            <div class="flex flex-col items-center justify-center space-y-1">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" 
                                                           name="selected_benefits[{{ $plan->id }}][{{ $category->id }}]" 
                                                           value="{{ $baseAmount }}"
                                                           data-plan="{{ $plan->id }}"
                                                           data-category="{{ $category->id }}"
                                                           data-category-name="{{ $category->name }}"
                                                           data-optical="{{ $isOptical ? '1' : '0' }}"
                                                           data-dental="{{ $isDental ? '1' : '0' }}"
                                                           data-benefit-amount="{{ $benefitAmount }}"
                                                           class="benefit-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 {{ $isInpatient ? 'inpatient-checkbox' : '' }}"
                                                           {{ $isInpatient ? 'checked required' : '' }}
                                                           {{ $oldSelected ? 'checked' : '' }}
                                                           {{ !$isSelected ? 'disabled' : '' }}>
                                                    <span class="ml-1 text-xs text-slate-600">Select</span>
                                                </label>
                                                @if($baseAmount > 0 && !($client->exists ?? false))
                                                    <span class="text-xs font-semibold text-slate-900 mt-1" title="Base Amount (Client Pays)">{{ number_format($baseAmount, 0, '.', ',') }}</span>
                                                @endif
                                                @if($benefitAmount > 0 && $benefitAmount != $baseAmount)
                                                    <span class="text-xs text-slate-500" title="Benefit Amount (Insurance Covers)">Cover: {{ number_format($benefitAmount, 0, '.', ',') }}</span>
                                                @endif
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
                <input type="date" name="desired_start_date" id="desired_start_date" value="{{ old('desired_start_date', now()->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-slate-500 mt-1">Defaults to today. Coverage and benefits start from this date.</p>
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

    @if($method === 'POST' && isset($insuranceCompany))
    <!-- Payment Details Card (always visible on create) -->
    <div class="border border-slate-300 rounded-lg p-6 bg-slate-50">
        <h2 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-300 pb-2">Payment Details</h2>
        <p class="text-sm text-slate-600 mb-4">How will the client pay the premium? For Mobile Money a payment prompt will be sent to the number you provide. Other methods are recorded manually within the grace period. <span class="text-slate-500">Select a plan above to enable this section.</span></p>
        @php
            $allowedMethods = $insuranceCompany->payment_methods ?? [];
            $methodOptions = \App\Models\InsuranceCompany::getPaymentMethodOptions();
            $options = empty($allowedMethods) ? $methodOptions : array_intersect_key($methodOptions, array_flip($allowedMethods));
        @endphp
        <div id="premium-payment-method-section" class="space-y-4">
            <div>
                <label for="premium_payment_method" class="block text-sm font-medium text-slate-700 mb-2">
                    Premium payment method <span class="text-red-500">*</span>
                </label>
                <select 
                    name="premium_payment_method" 
                    id="premium_payment_method" 
                    class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">— Select payment method —</option>
                    @foreach($options as $value => $label)
                        <option value="{{ $value }}" {{ old('premium_payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div id="premium-payment-phone-wrap" class="hidden">
                <label for="premium_payment_phone" class="block text-sm font-medium text-slate-700 mb-2">
                    Payment phone number (Mobile Money) <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="premium_payment_phone" 
                    id="premium_payment_phone" 
                    value="{{ old('premium_payment_phone') }}" 
                    placeholder="e.g. 256701234567 or 0701234567" 
                    class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="mt-1 text-xs text-slate-500">A payment prompt will be sent to this number. The client approves on their phone to complete the premium payment.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Form Actions -->
    <div class="flex justify-between items-center pt-4 border-t border-slate-200">
        <button type="button" onclick="autoGenerateForm()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150">
            🔄 Auto Generate (Test)
        </button>
        <div class="flex gap-4">
            <a href="{{ route('clients.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                {{ $method === 'PUT' ? 'Update' : 'Submit' }} Application
            </button>
        </div>
    </div>

    <!-- Premium Calculation Display (shown below submit button) -->
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
                <input type="number" name="number_of_dependents" id="number_of_dependents" value="{{ old('number_of_dependents', 0) }}" min="0" max="20" readonly class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-700 cursor-not-allowed">
                <p class="text-xs text-slate-500 mt-1">Automatically calculated based on dependents entered above</p>
            </div>
        </div>

        <div class="bg-white rounded-lg p-4 border border-slate-200">
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">Base Premium (Principal Member):</span>
                    <span class="text-sm font-bold text-slate-900" id="base-premium">UGX 0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">
                        Dependents Premium (<span id="dependents-count">0</span> dependents):
                        <span id="dependents-tier-info" class="text-xs text-slate-500 ml-2"></span>
                    </span>
                    <span class="text-sm font-bold text-slate-900" id="dependents-premium">UGX 0.00</span>
                </div>
                <div class="border-t border-slate-300 pt-3 flex justify-between items-center">
                    <span class="text-base font-semibold text-slate-900">Subtotal Premium:</span>
                    <span class="text-base font-bold text-blue-600" id="subtotal-premium">UGX 0.00</span>
                </div>
                <div id="premium-adjustments-container" style="display: none;">
                    <div class="border-t border-slate-300 pt-3 mt-3">
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">Medical Question Adjustments:</h4>
                        <div id="premium-adjustments-list" class="space-y-1"></div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">Insurance Training Levy:</span>
                    <span class="text-sm font-bold text-slate-900" id="training-levy">UGX 0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">Stamp Duty:</span>
                    <span class="text-sm font-bold text-slate-900" id="stamp-duty">UGX 35,000.00</span>
                </div>
                @if($method === 'POST')
                <input type="hidden" name="kashtre_service_charge" id="kashtre_service_charge" value="0">
                <input type="hidden" name="kashtre_connected_business_id" id="kashtre_connected_business_id" value="">
                <div id="kashtre-service-charge-row" class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">
                        Service charge
                        <span class="block text-xs text-slate-500 font-normal" id="kashtre-service-charge-hint">On premium + levy + stamp duty</span>
                    </span>
                    <span class="text-sm font-bold text-slate-900" id="kashtre-service-charge">UGX 0.00</span>
                </div>
                @endif
                <div id="deductible-adjustments-container" style="display: none;">
                    <div class="border-t border-slate-300 pt-3 mt-3">
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">Deductible Adjustments:</h4>
                        <div id="deductible-adjustments-list" class="space-y-1"></div>
                    </div>
                </div>
                <div id="coverage-limit-adjustments-container" style="display: none;">
                    <div class="border-t border-slate-300 pt-3 mt-3">
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">Coverage Limit Adjustments:</h4>
                        <div id="coverage-limit-adjustments-list" class="space-y-1"></div>
                        <p class="text-xs text-slate-500 mt-2">These adjustments affect annual/lifetime coverage limits and are noted for underwriter review.</p>
                    </div>
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

</form>

@if($method === 'POST')
<div id="duplicate-client-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-lg bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Possible duplicate client found</h3>
            <p class="mt-1 text-sm text-slate-600">Names and date of birth match an existing client record.</p>
        </div>
        <div class="space-y-2 px-6 py-4 text-sm text-slate-700">
            <p><span class="font-medium">Name:</span> <span id="duplicate-client-name">-</span></p>
            <p><span class="font-medium">DOB:</span> <span id="duplicate-client-dob">-</span></p>
            <p><span class="font-medium">ID/Passport:</span> <span id="duplicate-client-id">-</span></p>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
            <button type="button" id="duplicate-continue-manual" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Continue manually
            </button>
            <button type="button" id="duplicate-autofill" class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                Auto-fill existing client
            </button>
        </div>
    </div>
</div>
@endif

<script>

    // Pass plan data to JavaScript
    window.plansData = @json($plansData);
    
    // Pass medical questions data to JavaScript
    window.medicalQuestionsData = @json($medicalQuestionsData);
    
    let dependantCount = 1;
    let medicationCount = 1;

    @if($method === 'POST')
    const duplicateCheckUrl = "{{ route('clients.check-duplicate') }}";
    const duplicateModal = document.getElementById('duplicate-client-modal');
    const duplicateNameEl = document.getElementById('duplicate-client-name');
    const duplicateDobEl = document.getElementById('duplicate-client-dob');
    const duplicateIdEl = document.getElementById('duplicate-client-id');
    const duplicateAutofillBtn = document.getElementById('duplicate-autofill');
    const duplicateContinueBtn = document.getElementById('duplicate-continue-manual');
    let duplicateClientPayload = null;
    let duplicateCheckSuppressed = false;
    let duplicateLastKey = null;

    function normalizeForKey(value) {
        return (value || '').trim().toUpperCase();
    }

    function setFieldValue(name, value) {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field) return;
        if (field.type === 'radio') {
            const radio = document.querySelector(`input[name="${name}"][value="${value}"]`);
            if (radio) radio.checked = true;
            return;
        }
        field.value = value ?? '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function hideDuplicateModal() {
        if (!duplicateModal) return;
        duplicateModal.classList.add('hidden');
        duplicateModal.classList.remove('flex');
    }

    function showDuplicateModal() {
        if (!duplicateModal) return;
        duplicateModal.classList.remove('hidden');
        duplicateModal.classList.add('flex');
    }

    async function checkDuplicateClient() {
        if (duplicateCheckSuppressed) return;
        const firstName = document.getElementById('first_name')?.value?.trim();
        const surname = document.getElementById('surname')?.value?.trim();
        const dateOfBirth = document.getElementById('date_of_birth')?.value?.trim();

        if (!firstName || !surname || !dateOfBirth) return;

        const key = `${normalizeForKey(firstName)}|${normalizeForKey(surname)}|${dateOfBirth}`;
        if (duplicateLastKey === key) return;
        duplicateLastKey = key;

        try {
            const response = await fetch(`${duplicateCheckUrl}?first_name=${encodeURIComponent(firstName)}&surname=${encodeURIComponent(surname)}&date_of_birth=${encodeURIComponent(dateOfBirth)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return;
            const result = await response.json();
            if (!result.duplicate || !result.client) return;

            duplicateClientPayload = result.client;
            duplicateNameEl.textContent = `${result.client.first_name || ''} ${result.client.surname || ''} ${result.client.other_names || ''}`.trim() || '-';
            duplicateDobEl.textContent = result.client.date_of_birth || '-';
            duplicateIdEl.textContent = result.client.id_passport_no || '-';
            showDuplicateModal();
        } catch (error) {
            console.warn('Duplicate check failed', error);
        }
    }

    duplicateAutofillBtn?.addEventListener('click', function () {
        if (!duplicateClientPayload) return;
        duplicateCheckSuppressed = true;
        const fields = [
            'title','surname','first_name','other_names','id_passport_no','tin','date_of_birth','marital_status',
            'height','weight','employer_name','occupation','nationality','home_physical_address','office_physical_address',
            'home_telephone','office_telephone','cell_phone','whatsapp_line','email','next_of_kin_surname',
            'next_of_kin_first_name','next_of_kin_other_names','next_of_kin_title','next_of_kin_relation',
            'next_of_kin_id_passport_no','next_of_kin_cell_phone','next_of_kin_email','next_of_kin_post_address',
            'next_of_kin_physical_address'
        ];
        fields.forEach((field) => setFieldValue(field, duplicateClientPayload[field] ?? ''));
        if (duplicateClientPayload.gender) {
            const genderRadio = document.querySelector(`input[name="gender"][value="${duplicateClientPayload.gender}"]`);
            if (genderRadio) genderRadio.checked = true;
        }
        hideDuplicateModal();
    });

    duplicateContinueBtn?.addEventListener('click', function () {
        duplicateCheckSuppressed = true;
        hideDuplicateModal();
    });

    ['first_name', 'surname', 'date_of_birth'].forEach((fieldId) => {
        const el = document.getElementById(fieldId);
        if (!el) return;
        el.addEventListener('blur', checkDuplicateClient);
        el.addEventListener('change', checkDuplicateClient);
    });
    @endif

    // Auto-generate form with test data — fills every visible control (principal, NOK, plan benefits, medical, payment, dependant)
    function autoGenerateForm() {
        const form = document.querySelector('form[action*="clients"]') || document.querySelector('form[enctype="multipart/form-data"]') || document.querySelector('form');
        const rnd = (n) => Math.floor(Math.random() * n);
        const ts = Date.now();
        const idNum = 'CM' + String(Math.floor(100000000 + Math.random() * 900000000));
        const phone = '+2567' + String(1000000 + rnd(8999999)).padStart(9, '0').slice(0, 10);

        const firstNames = ['JOHN', 'PETER', 'DAVID', 'MICHAEL', 'SIMON', 'PAUL'];
        const surnames = ['DOE', 'SMITH', 'KATENDE', 'SSEMUWEMBA', 'KASULE'];
        const pick = (a) => a[rnd(a.length)];

        const pFirst = pick(firstNames);
        const pSur = pick(surnames);
        const pOther = 'NICHOLAS';
        const kFirst = pick(firstNames.filter((x) => x !== pFirst)) || 'JANE';
        const kSur = pick(surnames.filter((x) => x !== pSur)) || 'DOE';

        function dispatch(el) {
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function setRadioName(name, value) {
            const r = document.querySelector(`input[name="${CSS.escape(name)}"][value="${CSS.escape(value)}"]`);
            if (r) {
                r.checked = true;
                dispatch(r);
            }
        }

        function setVal(el, val) {
            if (!el || el.readOnly || el.disabled) return;
            if (el.type === 'radio' || el.type === 'checkbox') return;
            el.value = val;
            dispatch(el);
        }

        function fillById(id, val) {
            setVal(document.getElementById(id), val);
        }

        // --- Principal
        fillById('title', 'Mr');
        fillById('surname', pSur);
        fillById('first_name', pFirst);
        fillById('other_names', pOther);
        fillById('id_passport_no', idNum);
        setRadioName('gender', 'Male');
        fillById('tin', '100' + String(ts).slice(-7));
        fillById('date_of_birth', '1985-06-15');
        fillById('marital_status', 'Married');
        fillById('height', "5'10\"");
        fillById('weight', '78');
        fillById('occupation', 'Professional');
        fillById('employer_name', 'Test Employer Ltd');
        fillById('nationality', 'Ugandan');
        fillById('home_physical_address', 'Plot 10 Test Street, Kampala');
        fillById('office_physical_address', 'Plot 20 Office Park, Kampala');
        fillById('home_telephone', '0414' + rnd(999999));
        fillById('office_telephone', '0312' + rnd(999999));
        fillById('cell_phone', phone);
        fillById('whatsapp_line', phone);
        fillById('email', `${pFirst.toLowerCase()}.${pSur.toLowerCase()}.${ts}@example.test`);

        // --- Next of kin (actual field names on this form)
        fillById('next_of_kin_title', 'Mrs');
        fillById('next_of_kin_surname', kSur);
        fillById('next_of_kin_first_name', kFirst);
        fillById('next_of_kin_other_names', 'ANN');
        fillById('next_of_kin_relation', 'Spouse');
        fillById('next_of_kin_id_passport_no', 'CM' + String(Math.floor(100000000 + Math.random() * 900000000)));
        fillById('next_of_kin_cell_phone', '+2567' + String(1000000 + rnd(8999999)).slice(0, 9));
        fillById('next_of_kin_email', `${kFirst.toLowerCase()}.${kSur.toLowerCase()}.${ts}@example.test`);
        fillById('next_of_kin_post_address', 'P.O. Box ' + (1000 + rnd(9000)) + ', Kampala');
        fillById('next_of_kin_physical_address', 'Plot 7 Next Of Kin Road, Kampala');

        // --- Policy / copay / deductible (numeric fields use ids from blade)
        fillById('copay_amount', '20000');
        fillById('copay_max_limit', '200000');
        fillById('coinsurance_percentage', '10');
        fillById('insurance_payable_percentage', '100');
        fillById('premium_grace_days', '30');
        fillById('active_period_days', '365');
        setRadioName('has_deductible', '1');
        fillById('deductible_amount', '100000');
        const copayContrib = document.querySelector('input[name="copay_contributes_to_deductible"]');
        const coinsContrib = document.querySelector('input[name="coinsurance_contributes_to_deductible"]');
        if (copayContrib) {
            copayContrib.checked = true;
            dispatch(copayContrib);
        }
        if (coinsContrib) {
            coinsContrib.checked = true;
            dispatch(coinsContrib);
        }
        setRadioName('telemedicine_only', '0');

        // --- Declaration
        fillById('desired_start_date', new Date().toISOString().split('T')[0]);
        fillById('agent_broker_name', 'Test Agent');

        // --- First plan + all enabled benefits for that plan row
        const firstPlanRadio = document.querySelector('input.plan-radio') || document.querySelector('input[name="plan_id"]');
        if (firstPlanRadio) {
            firstPlanRadio.checked = true;
            dispatch(firstPlanRadio);
        }

        function tickAllBenefits() {
            document.querySelectorAll('.benefit-checkbox:not(:disabled)').forEach((cb) => {
                cb.checked = true;
                dispatch(cb);
            });
        }

        function fillMedicalAndMedication() {
            const root = form || document;
            document.querySelectorAll('input.question-response[type="radio"][value="no"]').forEach((radio) => {
                radio.checked = true;
                dispatch(radio);
            });
            root.querySelectorAll('textarea.question-response, textarea[name*="medical_questions"][name*="[response]"]').forEach((ta) => {
                if (!ta.name || ta.name.includes('[additional_info]')) return;
                if (!ta.value.trim()) {
                    ta.value = 'Not applicable (auto-fill test).';
                    dispatch(ta);
                }
            });
            root.querySelectorAll('input.question-response[type="date"], input[name*="medical_questions"][name*="[response]"][type="date"]').forEach((inp) => {
                if (!inp.value) {
                    inp.value = '1990-01-01';
                    dispatch(inp);
                }
            });
            root.querySelectorAll('input.question-response[type="number"], input[name*="medical_questions"][name*="[response]"][type="number"]').forEach((inp) => {
                if (inp.value === '' || inp.value === null) {
                    inp.value = '0';
                    dispatch(inp);
                }
            });
            root.querySelectorAll('textarea[name*="medical_questions"][name*="additional_info"]').forEach((ta) => {
                if (!ta.value.trim()) {
                    ta.value = 'N/A';
                    dispatch(ta);
                }
            });
            root.querySelectorAll('input[name^="medications["]').forEach((inp) => {
                if (!inp.value.trim()) {
                    if (inp.type === 'date') inp.value = '2020-01-01';
                    else inp.value = 'Test';
                    dispatch(inp);
                }
            });
        }

        function fillPremiumPayment() {
            const pm = document.getElementById('premium_payment_method');
            if (pm && pm.tagName === 'SELECT') {
                const opt = Array.from(pm.options).find((o) => o.value);
                if (opt) {
                    pm.value = opt.value;
                    dispatch(pm);
                }
                if (pm.value === 'mobile_money') {
                    const wrap = document.getElementById('premium-payment-phone-wrap');
                    if (wrap) wrap.classList.remove('hidden');
                    fillById('premium_payment_phone', phone.replace('+', ''));
                }
            }
        }

        function fillDependantSection(section) {
            if (!section) return;
            const q = (sel) => section.querySelector(sel);
            section.querySelectorAll('select[name*="[title]"]').forEach((s) => setVal(s, 'Miss'));
            setVal(q('input[name*="[surname]"]'), 'DOE');
            setVal(q('input[name*="[first_name]"]'), 'MARY');
            setVal(q('input[name*="[other_names]"]'), 'JANE');
            setVal(q('input[name*="[id_passport_no]"]'), 'CM' + String(Math.floor(100000000 + Math.random() * 900000000)));
            setVal(q('input[name*="[date_of_birth]"]'), '2012-04-10');
            setVal(q('input[name*="[relation_to_principal]"]'), 'Child');
            setVal(q('input[name*="[occupation]"]'), 'Student');
            setVal(q('input[name*="[height]"]'), "4'2\"");
            setVal(q('input[name*="[weight]"]'), '32');
            section.querySelectorAll('select[name*="[marital_status]"]').forEach((s) => setVal(s, 'Single'));
            const female = section.querySelector('input[name*="[gender]"][value="Female"]');
            if (female) {
                female.checked = true;
                dispatch(female);
            }
        }

        function fillRemainingEmpty() {
            if (!form) return;
            form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="radio"]):not([type="checkbox"]):not([readonly]), select:not([disabled]), textarea:not([readonly])').forEach((el) => {
                if (el.readOnly || el.disabled || el.name === 'number_of_dependents') return;
                if (el.type === 'file') return;
                if (el.value && String(el.value).trim() !== '') return;
                if (el.tagName === 'SELECT') {
                    const opts = Array.from(el.options).filter((o) => o.value !== '');
                    if (opts.length) {
                        el.value = opts[0].value;
                        dispatch(el);
                    }
                    return;
                }
                if (el.type === 'date') el.value = '2000-01-01';
                else if (el.type === 'number') el.value = '0';
                else el.value = 'TEST';
                dispatch(el);
            });
        }

        // Stagger so plan-specific UI enables before benefits / premium block
        setTimeout(() => {
            tickAllBenefits();
            fillMedicalAndMedication();
            fillPremiumPayment();
        }, 250);

        setTimeout(() => {
            const addBtn = document.getElementById('add-dependant-btn');
            if (addBtn && addBtn.style.display !== 'none') {
                addDependant();
                setTimeout(() => {
                    const sections = document.querySelectorAll('.dependant-section');
                    fillDependantSection(sections[sections.length - 1]);
                }, 80);
            }
        }, 280);

        setTimeout(() => {
            fillRemainingEmpty();
            if (typeof calculatePremium === 'function') calculatePremium();
            if (typeof updateDependentsCount === 'function') updateDependentsCount();
        }, 550);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Count valid dependants (those with at least first name or surname filled)
    function countValidDependants() {
        const sections = document.querySelectorAll('.dependant-section');
        let count = 0;
        
        sections.forEach(section => {
            const firstName = section.querySelector('input[name*="[first_name]"]')?.value.trim();
            const surname = section.querySelector('input[name*="[surname]"]')?.value.trim();
            
            // Count as valid if at least first name or surname is filled
            if (firstName || surname) {
                count++;
            }
        });
        
        return count;
    }
    
    // Update number of dependents field
    function updateDependentsCount() {
        const count = countValidDependants();
        const dependentsField = document.getElementById('number_of_dependents');
        if (dependentsField) {
            dependentsField.value = count;
            // Trigger premium calculation
            if (typeof calculatePremium === 'function') {
                calculatePremium();
            }
        }
    }
    
    // Add dependant
    function addDependant() {
        if (dependantCount >= 8) {
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
        
        // Add event listeners to new inputs to auto-calculate count
        const newInputs = template.querySelectorAll('input, select');
        newInputs.forEach(input => {
            input.addEventListener('input', updateDependentsCount);
            input.addEventListener('change', updateDependentsCount);
        });
        
        // Show remove button
        template.querySelector('.remove-btn').classList.remove('hidden');
        
        container.appendChild(template);
        dependantCount++;
        
        if (dependantCount >= 8) {
            document.getElementById('add-dependant-btn').style.display = 'none';
        }
        
        // Update count after adding
        updateDependentsCount();
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
        
        // Update count after removing
        updateDependentsCount();
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
    function setupDeductibleToggle() {
        document.querySelectorAll('input[name="has_deductible"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const field = document.getElementById('deductible-amount-field');
                const input = document.getElementById('deductible_amount');
                if (field) {
                    field.style.display = this.value === '1' ? 'block' : 'none';
                    // Enable/disable the input field
                    if (input) {
                        input.disabled = this.value !== '1';
                        if (this.value === '1') {
                            input.focus();
                        }
                    }
                }
            });
        });
        
        // Initialize the field state on page load
        const checkedRadio = document.querySelector('input[name="has_deductible"]:checked');
        if (checkedRadio) {
            const field = document.getElementById('deductible-amount-field');
            const input = document.getElementById('deductible_amount');
            if (field) {
                field.style.display = checkedRadio.value === '1' ? 'block' : 'none';
            }
            if (input) {
                input.disabled = checkedRadio.value !== '1';
            }
        }
    }

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
            
            // Recalculate premium when medical question response changes
            calculatePremium();
        });
    });
    
    // Also listen for text/date/number medical question inputs
    document.querySelectorAll('input[name^="medical_questions"], textarea[name^="medical_questions"]').forEach(input => {
        if (!input.classList.contains('question-response')) {
            input.addEventListener('input', function() {
                calculatePremium();
            });
        }
    });

    // Before form submission, convert medication tables to JSON and handle errors
    document.querySelector('form')?.addEventListener('submit', function(e) {
        // Store original button text outside try-catch for use in catch block
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton ? submitButton.textContent : 'Submit';
        
        try {
            console.log('Form submission started');
            
            // Show loading state
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
            }
            
            // Remove any existing error messages
            const existingError = document.getElementById('form-submission-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Find all medication tables and convert to JSON
            try {
                document.querySelectorAll('[id^="medication-tbody-"]').forEach(tbody => {
                    try {
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
                    } catch (medError) {
                        console.error('Error processing medication table:', medError);
                        // Log to server if possible
                        if (window.fetch) {
                            fetch('/api/v1/log-error', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({
                                    error: 'Medication table processing error',
                                    message: medError.message,
                                    stack: medError.stack,
                                    questionId: tbody.id
                                })
                            }).catch(err => console.error('Failed to log error:', err));
                        }
                    }
                });
            } catch (medTableError) {
                console.error('Error processing medication tables:', medTableError);
                throw medTableError;
            }
            
            console.log('Form validation passed, submitting...');
            
            // Log form data for debugging
            const formData = new FormData(this);
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            console.log('Form data being submitted:', formDataObj);
            
            // Allow form to submit normally - don't prevent default
            // The form will submit to the server
            
        } catch (error) {
            console.error('Form submission error:', error);
            
            // Log error to server
            if (window.fetch) {
                fetch('/api/v1/log-error', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        error: 'Form submission error',
                        message: error.message,
                        stack: error.stack,
                        url: window.location.href
                    })
                }).catch(err => console.error('Failed to log error to server:', err));
            }
            
            // Prevent form submission
            e.preventDefault();
            
            // Show error message to user
            const form = document.querySelector('form');
            let errorDiv = document.getElementById('form-submission-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'form-submission-error';
                errorDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
                form.insertBefore(errorDiv, form.firstChild);
            }
            
            errorDiv.innerHTML = `
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-red-800 mb-1">Error Submitting Form</h4>
                        <p class="text-sm text-red-700 mb-2">${error.message || 'An unexpected error occurred. Please try again.'}</p>
                        <p class="text-xs text-red-600">Error details have been logged. If this problem persists, please contact support.</p>
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
            
            return false;
        }
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
    
    let lastPremiumParts = { subtotal: 0, training: 0, stamp: 0 };

    function updateTotalPremiumDueDisplay() {
        const kashtre = typeof kashtreServiceCharge !== 'undefined' ? (parseFloat(kashtreServiceCharge) || 0) : 0;
        const total = lastPremiumParts.subtotal + lastPremiumParts.training + lastPremiumParts.stamp + kashtre;
        const totalEl = document.getElementById('total-premium-due');
        if (totalEl) {
            totalEl.textContent = formatCurrency(total);
        }
    }

    @if($method === 'POST')
    const kashtreChargeCalculateUrl = @json(route('clients.kashtre-service-charge.calculate'));
    let kashtreServiceCharge = 0;
    let kashtreChargeFetchTimer = null;

    function refreshKashtreServiceCharge(chargeableBase) {
        const row = document.getElementById('kashtre-service-charge-row');
        const amountEl = document.getElementById('kashtre-service-charge');
        const hintEl = document.getElementById('kashtre-service-charge-hint');
        const hiddenCharge = document.getElementById('kashtre_service_charge');
        const hiddenBusiness = document.getElementById('kashtre_connected_business_id');

        if (!row || !kashtreChargeCalculateUrl) {
            return;
        }

        chargeableBase = parseFloat(chargeableBase) || 0;
        clearTimeout(kashtreChargeFetchTimer);
        row.style.display = 'flex';

        if (chargeableBase <= 0) {
            kashtreServiceCharge = 0;
            if (amountEl) amountEl.textContent = formatCurrency(0);
            if (hintEl) hintEl.textContent = 'On premium + levy + stamp duty';
            if (hiddenCharge) hiddenCharge.value = '0';
            if (hiddenBusiness) hiddenBusiness.value = '';
            updateTotalPremiumDueDisplay();
            return;
        }

        kashtreChargeFetchTimer = setTimeout(async function () {
            if (amountEl) amountEl.textContent = 'Calculating…';

            try {
                const url = new URL(kashtreChargeCalculateUrl, window.location.origin);
                url.searchParams.set('chargeable_base', String(chargeableBase));
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                kashtreServiceCharge = parseFloat(data.amount) || 0;

                if (amountEl) {
                    amountEl.textContent = data.formatted_service_charge || formatCurrency(kashtreServiceCharge);
                }
                if (hintEl) {
                    if (data.has_connection === false) {
                        hintEl.textContent = 'No connected clinic — configure in Settings';
                    } else if (data.tier && data.tier.type === 'percentage') {
                        hintEl.textContent = data.tier.amount + '% on UGX ' + chargeableBase.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    } else {
                        hintEl.textContent = 'On premium + levy + stamp duty';
                    }
                }
                if (hiddenCharge) hiddenCharge.value = String(kashtreServiceCharge);
                if (hiddenBusiness && data.connected_business_id) {
                    hiddenBusiness.value = String(data.connected_business_id);
                }
            } catch (e) {
                console.warn('Kashtre service charge lookup failed', e);
                kashtreServiceCharge = 0;
                if (amountEl) amountEl.textContent = 'Unavailable';
                if (hintEl) hintEl.textContent = 'Could not reach Kashtre API';
                if (hiddenCharge) hiddenCharge.value = '0';
            }

            updateTotalPremiumDueDisplay();
        }, 350);
    }
    @endif

    // Premium calculation function
    function calculatePremium() {
        const selectedPlan = document.querySelector('input[name="plan_id"]:checked');
        const premiumCalcDiv = document.getElementById('premium-calculation');
        
        if (!selectedPlan) {
            premiumCalcDiv.style.display = 'none';
            const kashtreRow = document.getElementById('kashtre-service-charge-row');
            if (kashtreRow) {
                kashtreRow.style.display = 'none';
            }
            var pmSelect = document.getElementById('premium_payment_method');
            if (pmSelect) { pmSelect.required = false; }
            var phoneWrap = document.getElementById('premium-payment-phone-wrap');
            if (phoneWrap) { phoneWrap.classList.add('hidden'); }
            var phoneInput = document.getElementById('premium_payment_phone');
            if (phoneInput) { phoneInput.required = false; }
            return;
        }

        premiumCalcDiv.style.display = 'block';
        var pmSelect = document.getElementById('premium_payment_method');
        if (pmSelect) { pmSelect.required = true; }
        if (typeof syncPremiumPaymentPhoneVisibility === 'function') syncPremiumPaymentPhoneVisibility();
        
        const planId = parseInt(selectedPlan.value);
        const numberOfDependents = parseInt(document.getElementById('number_of_dependents').value) || 0;
        
        // Get plan data
        const planData = window.plansData && window.plansData[planId] ? window.plansData[planId] : {
            dependent_coverage_multiplier: 0.50,
            dependent_multiplier_tiers: null,
            dependent_multiplier_floor: null,
            insurance_training_levy_percentage: 0.50,
            stamp_duty_amount: 35000,
            premium_calculation_method: 'benefit_based',
            base_premium: 0,
        };
        
        // Ensure all plan data values are numbers
        const dependentMultiplier = parseFloat(planData.dependent_coverage_multiplier) || 0.50;
        const trainingLevyPercentage = parseFloat(planData.insurance_training_levy_percentage) || 0.50;
        const stampDuty = parseFloat(planData.stamp_duty_amount) || 35000;
        const planBasePremium = parseFloat(planData.base_premium) || 0;
        
        // Get all checked benefit checkboxes for the selected plan
        const checkedBenefits = document.querySelectorAll(
            `.benefit-checkbox[data-plan="${planId}"]:checked`
        );
        
        // Calculate base premium from selected benefits (using base_amount - what client pays)
        let basePremium = 0;
        checkedBenefits.forEach(checkbox => {
            const baseAmount = parseFloat(checkbox.value) || 0; // This is now base_amount, not benefit_amount
            if (!isNaN(baseAmount)) {
                basePremium += baseAmount;
            }
        });
        
        // Apply plan's calculation method
        if (planData.premium_calculation_method === 'fixed') {
            basePremium = planBasePremium;
        } else if (planData.premium_calculation_method === 'hybrid') {
            basePremium = (planBasePremium || 0) + (basePremium || 0);
        }
        // else 'benefit_based' - already calculated above
        
        // Ensure basePremium is a number
        basePremium = parseFloat(basePremium) || 0;
        if (isNaN(basePremium)) basePremium = 0;
        
        // Calculate dependents premium using tiered multipliers
        let dependentsPremium = 0;
        if (numberOfDependents > 0) {
            const tiers = planData.dependent_multiplier_tiers || [];
            const floor = planData.dependent_multiplier_floor !== null && planData.dependent_multiplier_floor !== undefined ? parseFloat(planData.dependent_multiplier_floor) : null;
            const legacyMultiplier = parseFloat(dependentMultiplier) || 0.50;
            
            if (tiers && tiers.length > 0) {
                // Use tiered multipliers
                const tierInfo = [];
                for (let i = 0; i < numberOfDependents; i++) {
                    let multiplier;
                    let tierLabel;
                    if (i < tiers.length) {
                        multiplier = parseFloat(tiers[i]) || 0;
                        tierLabel = `Tier ${i + 1} (${(multiplier * 100).toFixed(0)}%)`;
                    } else if (floor !== null && !isNaN(floor)) {
                        multiplier = floor;
                        tierLabel = `Floor (${(multiplier * 100).toFixed(0)}%)`;
                    } else {
                        multiplier = legacyMultiplier;
                        tierLabel = `Legacy (${(multiplier * 100).toFixed(0)}%)`;
                    }
                    dependentsPremium += parseFloat(basePremium) * multiplier;
                    tierInfo.push(`Dep ${i + 1}: ${tierLabel}`);
                }
                // Update tier info display
                const tierInfoEl = document.getElementById('dependents-tier-info');
                if (tierInfoEl && numberOfDependents > 0) {
                    tierInfoEl.textContent = `[${tierInfo.join(', ')}]`;
                    tierInfoEl.style.display = 'inline';
                }
            } else {
                // Use legacy multiplier
                dependentsPremium = parseFloat(basePremium) * parseFloat(legacyMultiplier) * parseFloat(numberOfDependents);
                // Hide tier info when using legacy
                const tierInfoEl = document.getElementById('dependents-tier-info');
                if (tierInfoEl) {
                    tierInfoEl.textContent = '';
                    tierInfoEl.style.display = 'none';
                }
            }
        }
        if (isNaN(dependentsPremium)) dependentsPremium = 0;
        
        // Calculate subtotal (before medical question adjustments)
        let subtotalPremium = parseFloat(basePremium) + parseFloat(dependentsPremium);
        if (isNaN(subtotalPremium)) subtotalPremium = 0;
        
        // Calculate monetary impact from medical questions
        let premiumAdjustment = 0;
        let deductibleAdjustment = 0;
        const premiumAdjustmentsList = [];
        const deductibleAdjustmentsList = [];
        const coverageLimitAdjustmentsList = [];
        
        if (window.medicalQuestionsData) {
            Object.keys(window.medicalQuestionsData).forEach(questionId => {
                const question = window.medicalQuestionsData[questionId];
                if (!question.has_monetary_impact || question.monetary_impact_type === 'none') {
                    return;
                }
                
                // Get response for this question
                // Try checked radio button first, then other inputs
                let responseInput = document.querySelector(`input[name="medical_questions[${questionId}][response]"]:checked`);
                if (!responseInput) {
                    responseInput = document.querySelector(`input[name="medical_questions[${questionId}][response]"]`);
                }
                if (!responseInput) {
                    responseInput = document.querySelector(`textarea[name="medical_questions[${questionId}][response]"]`);
                }
                
                if (!responseInput || !responseInput.value) {
                    return;
                }
                
                const response = (responseInput.value || '').toLowerCase().trim();
                const appliesTo = question.monetary_impact_applies_to_response || 'yes';
                
                // Check if response matches the trigger
                let shouldApply = false;
                if (question.question_type === 'yes_no') {
                    shouldApply = (response === appliesTo);
                } else {
                    // For text/date/number, check if response matches or contains the trigger
                    shouldApply = (response === appliesTo || response.includes(appliesTo));
                }
                
                if (shouldApply && question.monetary_impact_amount) {
                    let impactAmount = parseFloat(question.monetary_impact_amount) || 0;
                    if (isNaN(impactAmount)) impactAmount = 0;
                    
                    if (question.monetary_impact_type === 'premium_adjustment') {
                        if (question.monetary_impact_is_percentage) {
                            // Percentage of base premium
                            impactAmount = parseFloat(basePremium) * (parseFloat(impactAmount) / 100);
                            if (isNaN(impactAmount)) impactAmount = 0;
                        }
                        premiumAdjustment = parseFloat(premiumAdjustment) + parseFloat(impactAmount);
                        if (isNaN(premiumAdjustment)) premiumAdjustment = 0;
                        premiumAdjustmentsList.push({
                            amount: impactAmount,
                            description: question.monetary_impact_description || `Question ${questionId} adjustment`,
                            isPositive: impactAmount > 0
                        });
                    } else if (question.monetary_impact_type === 'deductible_adjustment') {
                        // For deductible adjustment, if percentage, we need a base deductible to calculate from
                        // For now, treat percentage as fixed amount (could be improved with base deductible)
                        // Note: Percentage-based deductible adjustments would need a base deductible value
                        deductibleAdjustment = parseFloat(deductibleAdjustment) + parseFloat(impactAmount);
                        if (isNaN(deductibleAdjustment)) deductibleAdjustment = 0;
                        deductibleAdjustmentsList.push({
                            amount: impactAmount,
                            description: question.monetary_impact_description || `Question ${questionId} adjustment`,
                            isPositive: impactAmount > 0
                        });
                    } else if (question.monetary_impact_type === 'coverage_limit_adjustment') {
                        // Coverage limit adjustments (stored for display and potential future use)
                        coverageLimitAdjustmentsList.push({
                            amount: impactAmount,
                            isPercentage: question.monetary_impact_is_percentage,
                            description: question.monetary_impact_description || `Question ${questionId} coverage limit adjustment`,
                            isPositive: impactAmount > 0
                        });
                    }
                }
            });
        }
        
        // Apply premium adjustment
        premiumAdjustment = parseFloat(premiumAdjustment) || 0;
        if (isNaN(premiumAdjustment)) premiumAdjustment = 0;
        subtotalPremium = parseFloat(subtotalPremium) + parseFloat(premiumAdjustment);
        if (isNaN(subtotalPremium)) subtotalPremium = 0;
        
        // Calculate insurance training levy using plan's percentage
        // trainingLevyPercentage is stored as a percentage (e.g., 0.50 = 0.5%), so divide by 100
        const trainingLevyPercent = parseFloat(trainingLevyPercentage) / 100;
        let trainingLevy = parseFloat(subtotalPremium) * parseFloat(trainingLevyPercent);
        if (isNaN(trainingLevy)) trainingLevy = 0;
        
        lastPremiumParts = {
            subtotal: parseFloat(subtotalPremium) || 0,
            training: parseFloat(trainingLevy) || 0,
            stamp: parseFloat(stampDuty) || 0,
        };

        // Update display
        document.getElementById('base-premium').textContent = formatCurrency(basePremium);
        document.getElementById('dependents-count').textContent = numberOfDependents;
        document.getElementById('dependents-premium').textContent = formatCurrency(dependentsPremium);
        document.getElementById('subtotal-premium').textContent = formatCurrency(subtotalPremium);
        document.getElementById('training-levy').textContent = formatCurrency(trainingLevy);
        document.getElementById('stamp-duty').textContent = formatCurrency(stampDuty);
        updateTotalPremiumDueDisplay();
        if (typeof refreshKashtreServiceCharge === 'function') {
            const chargeableBase = lastPremiumParts.subtotal + lastPremiumParts.training + lastPremiumParts.stamp;
            refreshKashtreServiceCharge(chargeableBase);
        }
        
        // Hide tier info if no dependents
        if (numberOfDependents === 0) {
            const tierInfoEl = document.getElementById('dependents-tier-info');
            if (tierInfoEl) {
                tierInfoEl.textContent = '';
                tierInfoEl.style.display = 'none';
            }
        }
        
        // Display premium adjustments
        const premiumAdjustmentsContainer = document.getElementById('premium-adjustments-container');
        const premiumAdjustmentsListEl = document.getElementById('premium-adjustments-list');
        if (premiumAdjustmentsList && premiumAdjustmentsList.length > 0) {
            if (premiumAdjustmentsContainer) premiumAdjustmentsContainer.style.display = 'block';
            if (premiumAdjustmentsListEl) {
                premiumAdjustmentsListEl.innerHTML = premiumAdjustmentsList.map(adj => {
                    const sign = adj.isPositive ? '+' : '';
                    const color = adj.isPositive ? 'text-red-600' : 'text-green-600';
                    return `<div class="flex justify-between items-center text-xs">
                        <span class="text-slate-600">${adj.description}:</span>
                        <span class="font-semibold ${color}">${sign}${formatCurrency(adj.amount)}</span>
                    </div>`;
                }).join('');
            }
        } else {
            if (premiumAdjustmentsContainer) premiumAdjustmentsContainer.style.display = 'none';
            if (premiumAdjustmentsListEl) premiumAdjustmentsListEl.innerHTML = '';
        }
        
        // Display deductible adjustments
        const deductibleAdjustmentsContainer = document.getElementById('deductible-adjustments-container');
        const deductibleAdjustmentsListEl = document.getElementById('deductible-adjustments-list');
        if (deductibleAdjustmentsList && deductibleAdjustmentsList.length > 0) {
            if (deductibleAdjustmentsContainer) deductibleAdjustmentsContainer.style.display = 'block';
            if (deductibleAdjustmentsListEl) {
                deductibleAdjustmentsListEl.innerHTML = deductibleAdjustmentsList.map(adj => {
                    const sign = adj.isPositive ? '+' : '';
                    const color = adj.isPositive ? 'text-red-600' : 'text-green-600';
                    return `<div class="flex justify-between items-center text-xs">
                        <span class="text-slate-600">${adj.description}:</span>
                        <span class="font-semibold ${color}">${sign}${formatCurrency(adj.amount)}</span>
                    </div>`;
                }).join('');
            }
        } else {
            if (deductibleAdjustmentsContainer) deductibleAdjustmentsContainer.style.display = 'none';
            if (deductibleAdjustmentsListEl) deductibleAdjustmentsListEl.innerHTML = '';
        }
        
        // Display coverage limit adjustments
        const coverageLimitAdjustmentsContainer = document.getElementById('coverage-limit-adjustments-container');
        const coverageLimitAdjustmentsListEl = document.getElementById('coverage-limit-adjustments-list');
        if (coverageLimitAdjustmentsList && coverageLimitAdjustmentsList.length > 0) {
            if (coverageLimitAdjustmentsContainer) coverageLimitAdjustmentsContainer.style.display = 'block';
            if (coverageLimitAdjustmentsListEl) {
                coverageLimitAdjustmentsListEl.innerHTML = coverageLimitAdjustmentsList.map(adj => {
                    const amountDisplay = adj.isPercentage 
                        ? `${adj.amount}%` 
                        : formatCurrency(adj.amount);
                    const sign = adj.isPositive ? '+' : '';
                    return `<div class="flex justify-between items-center text-xs">
                        <span class="text-slate-600">${adj.description}:</span>
                        <span class="font-semibold text-orange-600">${sign}${amountDisplay}</span>
                    </div>`;
                }).join('');
            }
        } else {
            if (coverageLimitAdjustmentsContainer) coverageLimitAdjustmentsContainer.style.display = 'none';
            if (coverageLimitAdjustmentsListEl) coverageLimitAdjustmentsListEl.innerHTML = '';
        }
    }
    
    function syncPremiumPaymentPhoneVisibility() {
        var pmSelect = document.getElementById('premium_payment_method');
        var phoneWrap = document.getElementById('premium-payment-phone-wrap');
        var phoneInput = document.getElementById('premium_payment_phone');
        if (!pmSelect || !phoneWrap || !phoneInput) return;
        var isMobileMoney = (pmSelect.value === 'mobile_money');
        if (isMobileMoney) {
            phoneWrap.classList.remove('hidden');
            phoneInput.required = true;
        } else {
            phoneWrap.classList.add('hidden');
            phoneInput.required = false;
        }
    }

    // Initialize: Add event listeners to existing dependant inputs for auto-calculation
    document.addEventListener('DOMContentLoaded', function() {
        // Setup deductible toggle
        setupDeductibleToggle();
        
        var pmSelect = document.getElementById('premium_payment_method');
        if (pmSelect) {
            pmSelect.addEventListener('change', syncPremiumPaymentPhoneVisibility);
            syncPremiumPaymentPhoneVisibility();
        }
        
        // Add event listeners to all existing dependant inputs
        const dependantInputs = document.querySelectorAll('#dependants-container input, #dependants-container select');
        dependantInputs.forEach(input => {
            input.addEventListener('input', updateDependentsCount);
            input.addEventListener('change', updateDependentsCount);
        });
        
        // Initial count calculation
        updateDependentsCount();
        
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
        
        // Calculate premium on page load if plan is already selected
        // This will update all premium-related displays
        if (document.querySelector('input[name="plan_id"]:checked')) {
            calculatePremium();
        }
    });
    
    // Format currency
    function formatCurrency(amount) {
        return 'UGX ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
</script>
