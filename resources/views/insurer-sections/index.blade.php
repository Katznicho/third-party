@extends('layouts.dashboard')

@section('title', 'Sections')
@section('page-title', 'Sections')

@section('content')
@include('partials.insurer-lookup-index', [
    'items' => $sections,
    'entityLabel' => 'Section',
    'entityLabelPlural' => 'Sections',
    'description' => 'Organizational sections for staff. Optional when creating or editing users.',
    'createRoute' => 'sections.create',
    'editRoute' => 'sections.edit',
    'destroyRoute' => 'sections.destroy',
    'showUsersCount' => true,
    'emptyMessage' => 'No sections yet. Add sections to assign staff optionally at enrollment.',
])
@endsection
