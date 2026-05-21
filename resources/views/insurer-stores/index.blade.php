@extends('layouts.dashboard')

@section('title', 'Stores')
@section('page-title', 'Stores')

@section('content')
@include('partials.insurer-lookup-index', [
    'items' => $stores,
    'entityLabel' => 'Store',
    'entityLabelPlural' => 'Stores',
    'description' => 'Storage or inventory locations for your organization.',
    'createRoute' => 'stores.create',
    'editRoute' => 'stores.edit',
    'destroyRoute' => 'stores.destroy',
    'emptyMessage' => 'No stores yet.',
])
@endsection
