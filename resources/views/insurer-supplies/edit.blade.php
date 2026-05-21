@extends('layouts.dashboard')

@section('title', 'Edit supply')
@section('page-title', 'Edit supply')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'Edit supply',
    'subtitle' => $supply->name,
    'indexRoute' => 'supplies.index',
    'formAction' => route('supplies.update', $supply),
    'formMethod' => 'PUT',
    'namePlaceholder' => 'Supply name',
    'record' => $supply,
])
@endsection
