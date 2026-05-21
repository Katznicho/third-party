@extends('layouts.dashboard')

@section('title', 'Create section')
@section('page-title', 'Create section')

@section('content')
@include('partials.insurer-lookup-form-page', [
    'pageTitle' => 'New section',
    'subtitle' => 'Add a section for your insurance company.',
    'indexRoute' => 'sections.index',
    'formAction' => route('sections.store'),
    'namePlaceholder' => 'e.g. Claims, Underwriting',
    'record' => null,
])
@endsection
