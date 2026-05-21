<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $pageTitle }}</h1>
            <p class="text-slate-600 mt-1 text-sm">{{ $subtitle }}</p>
        </div>
        <a href="{{ route($indexRoute) }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm font-medium hover:bg-slate-700">← Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-xl">
        <form action="{{ $formAction }}" method="POST" class="space-y-5">
            @csrf
            @if(($formMethod ?? 'POST') !== 'POST')
                @method($formMethod)
            @endif
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $record->name ?? '') }}" required maxlength="255"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="{{ $namePlaceholder }}">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" maxlength="1000"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                    placeholder="Optional notes">{{ old('description', $record->description ?? '') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route($indexRoute) }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm hover:bg-slate-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>
