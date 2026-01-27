@extends('layouts.dashboard')

@section('title', 'Edit Client')
@section('page-title', 'Edit Client')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Client</h1>
            <p class="text-slate-600 mt-1">Update client information</p>
        </div>
        <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-150">
            ← Back to Clients
        </a>
    </div>

    <!-- Edit Form - Same structure as create, but with existing data -->
    @include('clients.form', ['client' => $client, 'action' => route('clients.update', $client), 'method' => 'PUT', 'medicalQuestions' => $medicalQuestions])

</div>
@endsection
