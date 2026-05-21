@extends('layouts.dashboard')

@section('title', 'Create supply')
@section('page-title', 'Create supply')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'New supply',
    'subtitle' => 'Add a supply for your insurance company.',
    'indexRoute' => 'supplies.index',
    'formAction' => route('supplies.store'),
    'namePlaceholder' => 'e.g. Medical consumables',
    'record' => null,
])
@endsection
