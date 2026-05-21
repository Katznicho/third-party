<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $entityLabelPlural }}</h1>
            <p class="text-slate-600 mt-1 text-sm">{{ $description }}</p>
        </div>
        <a href="{{ route($createRoute) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            New {{ strtolower($entityLabel) }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($items->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Description</th>
                            @if($showUsersCount ?? false)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Staff users</th>
                            @endif
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                            <tr>
                                <td class="px-4 py-3 text-slate-900 font-medium">{{ $item->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->description ?? '—' }}</td>
                                @if($showUsersCount ?? false)
                                    <td class="px-4 py-3 text-slate-600">{{ $item->users_count }}</td>
                                @endif
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route($editRoute, $item) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-100 text-slate-800 hover:bg-slate-200">Edit</a>
                                    <form action="{{ route($destroyRoute, $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete this {{ strtolower($entityLabel) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-red-50 text-red-700 hover:bg-red-100">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $items->links() }}</div>
        @else
            <div class="p-8 text-center text-sm text-slate-500">{{ $emptyMessage }}</div>
        @endif
    </div>
</div>
