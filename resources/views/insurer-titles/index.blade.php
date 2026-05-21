@extends('layouts.dashboard')

@section('title', 'Titles')
@section('page-title', 'Titles')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Titles</h1>
            <p class="text-slate-600 mt-1 text-sm">Salutations for staff and client enrollment (e.g. Mr, Mrs, Dr). When none are defined, common defaults are used on client forms.</p>
        </div>
        <a href="{{ route('titles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            New title
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($titles->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Staff users</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($titles as $title)
                            <tr>
                                <td class="px-4 py-3 text-slate-900 font-medium">{{ $title->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $title->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $title->users_count }}</td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('titles.edit', $title) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-100 text-slate-800 hover:bg-slate-200">
                                        Edit
                                    </a>
                                    <form action="{{ route('titles.destroy', $title) }}" method="POST" class="inline" onsubmit="return confirm('Delete this title?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-red-50 text-red-700 hover:bg-red-100">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $titles->links() }}
            </div>
        @else
            <div class="p-8 text-center text-sm text-slate-500">
                No custom titles yet. Client forms use Mr, Mrs, Miss, and Dr until you add your own.
            </div>
        @endif
    </div>
</div>
@endsection
