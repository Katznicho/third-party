@extends('layouts.dashboard')

@section('title', 'Edit store')
@section('page-title', 'Edit store')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'Edit store',
    'subtitle' => $store->name,
    'indexRoute' => 'stores.index',
    'formAction' => route('stores.update', $store),
    'formMethod' => 'PUT',
    'namePlaceholder' => 'Store name',
    'record' => $store,
])
@endsection
