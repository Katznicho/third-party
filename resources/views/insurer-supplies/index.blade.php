@extends('layouts.dashboard')

@section('title', 'Supplies')
@section('page-title', 'Supplies')

@section('content')
@include('partials.insurer-lookup-index', [
    'items' => $supplies,
    'entityLabel' => 'Supply',
    'entityLabelPlural' => 'Supplies',
    'description' => 'Supply sources or categories for your organization.',
    'createRoute' => 'supplies.create',
    'editRoute' => 'supplies.edit',
    'destroyRoute' => 'supplies.destroy',
    'emptyMessage' => 'No supplies yet.',
])
@endsection
