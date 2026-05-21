@extends('layouts.dashboard')

@section('title', 'Edit section')
@section('page-title', 'Edit section')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'Edit section',
    'subtitle' => $section->name,
    'indexRoute' => 'sections.index',
    'formAction' => route('sections.update', $section),
    'formMethod' => 'PUT',
    'namePlaceholder' => 'Section name',
    'record' => $section,
])
@endsection
