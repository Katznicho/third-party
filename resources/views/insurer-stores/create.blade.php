@extends('layouts.dashboard')

@section('title', 'Create store')
@section('page-title', 'Create store')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'New store',
    'subtitle' => 'Add a store for your insurance company.',
    'indexRoute' => 'stores.index',
    'formAction' => route('stores.store'),
    'namePlaceholder' => 'e.g. Main warehouse',
    'record' => null,
])
@endsection
