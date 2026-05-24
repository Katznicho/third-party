@php
    $user = $user ?? null;
    $optionalFieldNames = [
        'surname', 'first_name', 'middle_name', 'national_id',
        'department_id', 'section_id', 'title_id', 'qualification_id',
        'gender', 'birth_date', 'marital_status',
    ];
    $openOptional = $errors->any() && collect($optionalFieldNames)->contains(fn ($f) => $errors->has($f));
    if (! $openOptional) {
        $openOptional = collect($optionalFieldNames)->contains(fn ($f) => filled(old($f)));
    }
@endphp

<details class="rounded-lg border border-slate-200 bg-slate-50/50" @if($openOptional) open @endif>
    <summary class="cursor-pointer select-none px-4 py-3 text-sm font-medium text-slate-800 list-none [&::-webkit-details-marker]:hidden">
        <span class="flex items-center justify-between gap-2">
            <span>Additional details <span class="font-normal text-slate-500">(optional)</span></span>
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </span>
    </summary>
    <div class="px-4 pb-4 pt-1 space-y-4 border-t border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="surname" class="block text-sm font-medium text-slate-700 mb-2">Surname</label>
                <input type="text" name="surname" id="surname" value="{{ old('surname', optional($user)->surname) }}" placeholder="Surname"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('surname') border-red-500 @enderror">
                @error('surname')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-2">First name</label>
                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', optional($user)->first_name) }}" placeholder="First name"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('first_name') border-red-500 @enderror">
                @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="middle_name" class="block text-sm font-medium text-slate-700 mb-2">Middle name</label>
                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', optional($user)->middle_name) }}" placeholder="Middle name"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('middle_name') border-red-500 @enderror">
                @error('middle_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <div>
                <label for="national_id" class="block text-sm font-medium text-slate-700 mb-2">National ID (NIN)</label>
                <input type="text" name="national_id" id="national_id" value="{{ old('national_id', optional($user)->national_id) }}" placeholder="National identification number"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('national_id') border-red-500 @enderror">
                @error('national_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="department_id" class="block text-sm font-medium text-slate-700 mb-2">Department</label>
                <select name="department_id" id="department_id"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('department_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) old('department_id', optional($user)->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @if($departments->isEmpty())
                    <p class="mt-1 text-xs text-slate-500">Add under <span class="font-medium">Settings → Departments</span>.</p>
                @endif
                @if(isset($user) && $user->department && ! $user->department_id)
                    <p class="mt-1 text-xs text-amber-700">Legacy department: {{ $user->department }}</p>
                @endif
            </div>
            <div>
                <label for="section_id" class="block text-sm font-medium text-slate-700 mb-2">Section</label>
                <select name="section_id" id="section_id"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('section_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ (string) old('section_id', optional($user)->section_id) === (string) $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                    @endforeach
                </select>
                @error('section_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @if($sections->isEmpty())
                    <p class="mt-1 text-xs text-slate-500">Add under <span class="font-medium">Settings → Sections</span>.</p>
                @endif
            </div>
            <div>
                <label for="title_id" class="block text-sm font-medium text-slate-700 mb-2">Title</label>
                <select name="title_id" id="title_id"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach($titles as $t)
                        <option value="{{ $t->id }}" {{ (string) old('title_id', optional($user)->title_id) === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('title_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="qualification_id" class="block text-sm font-medium text-slate-700 mb-2">Qualification</label>
                <select name="qualification_id" id="qualification_id"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('qualification_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach($qualifications as $q)
                        <option value="{{ $q->id }}" {{ (string) old('qualification_id', optional($user)->qualification_id) === (string) $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
                    @endforeach
                </select>
                @error('qualification_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="gender" class="block text-sm font-medium text-slate-700 mb-2">Gender</label>
                <select name="gender" id="gender"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gender') border-red-500 @enderror">
                    <option value="">—</option>
                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ old('gender', optional($user)->gender) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-2">Date of birth</label>
                <input type="date" name="birth_date" id="birth_date"
                    value="{{ old('birth_date', optional($user)->birth_date?->format('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('birth_date') border-red-500 @enderror">
                @error('birth_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="marital_status" class="block text-sm font-medium text-slate-700 mb-2">Marital status</label>
                <select name="marital_status" id="marital_status"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('marital_status') border-red-500 @enderror">
                    <option value="">—</option>
                    @foreach (['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'separated' => 'Separated', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ old('marital_status', optional($user)->marital_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('marital_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>
</details>
