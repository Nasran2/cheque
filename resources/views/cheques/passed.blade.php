@extends('layouts.app')

@section('title', 'Passed Cheques - Cheque Management System')
@section('page_title', 'Passed Cheques')
@section('mobile_title', 'Passed Cheques')

@section('content')
    @include('cheques.partials.list', [
        'pageTitle' => 'Passed Cheques',
        'pageDescription' => 'Review completed cheques that have been successfully passed.',
        'statusLabel' => 'Passed',
        'icon' => 'fa-regular fa-circle-check',
        'theme' => 'green',
        'routeName' => 'cheques.passed',
        'emptyText' => 'No passed cheques found.',
        'actions' => ['View', 'Print', 'Download PDF'],
    ])
@endsection
