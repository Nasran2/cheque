@extends('layouts.app')

@section('title', 'Returned Cheques - Cheque Management System')
@section('page_title', 'Returned Cheques')
@section('mobile_title', 'Returned Cheques')

@section('content')
    @include('cheques.partials.list', [
        'pageTitle' => 'Returned Cheques',
        'pageDescription' => 'Manage returned cheques and follow up on replacements.',
        'statusLabel' => 'Returned',
        'icon' => 'fa-solid fa-rotate-left',
        'theme' => 'red',
        'routeName' => 'cheques.returned',
        'emptyText' => 'No returned cheques found.',
        'actions' => ['View', 'Add Replacement Cheque', 'Print'],
    ])
@endsection
