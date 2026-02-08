@extends('layouts.dashboard')

@section('title', 'View Service Category')
@section('page-title', 'View Service Category')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Service Category Details</h2>
            <div class="flex gap-3">
                <a href="{{ route('service-categories.edit', $serviceCategory) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Edit
                </a>
                <form action="{{ route('service-categories.destroy', $serviceCategory) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Delete
                    </button>
                </form>
                <a href="{{ route('service-categories.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                    ← Back
                </a>
            </div>
        </div>
        <p class="text-slate-600">Service category detail view coming soon...</p>
    </div>
</div>
@endsection
