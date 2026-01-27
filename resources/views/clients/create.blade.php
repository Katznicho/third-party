@extends('layouts.dashboard')

@section('title', 'Create Client')
@section('page-title', 'Create Client')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Create New Client</h1>
            <p class="text-slate-600 mt-1">Add a new client to the system</p>
        </div>
        <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Clients
        </a>
    </div>

    <!-- Create Form - Uses shared form partial -->
    @php
        $client = new \App\Models\Client();
        $medicalQuestions = \App\Models\MedicalQuestion::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    @endphp
    @include('clients.form', ['client' => $client, 'action' => route('clients.store'), 'method' => 'POST', 'medicalQuestions' => $medicalQuestions])
</div>
@endsection
