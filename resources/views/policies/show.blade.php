@extends('layouts.dashboard')

@section('title', 'View Policy')
@section('page-title', 'View Policy')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Policy Details</h2>
        <p class="text-slate-600">Policy detail view coming soon...</p>
        <div class="mt-4">
            <a href="{{ route('policies.index') }}" class="text-blue-600 hover:text-blue-900">← Back to Policies</a>
        </div>
    </div>
</div>
@endsection
