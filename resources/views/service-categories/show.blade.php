@extends('layouts.dashboard')

@section('title', 'View Service Category')
@section('page-title', 'View Service Category')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Service Category Details</h2>
        <p class="text-slate-600">Service category detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('service-categories.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Service Categories</a>
        </div>
    </div>
</div>
@endsection
