@extends('layouts.app')

@section('title', 'Pending Cheques - Cheque Management System')
@section('page_title', 'Pending Cheques')
@section('mobile_title', 'Pending Cheques')

@section('content')
    @include('cheques.partials.list', [
        'pageTitle' => 'Pending Cheques',
        'pageDescription' => 'Track cheques waiting to be passed or returned.',
        'statusLabel' => 'Pending',
        'icon' => 'fa-regular fa-clock',
        'theme' => 'orange',
        'routeName' => 'cheques.pending',
        'emptyText' => 'No pending cheques found.',
        'actions' => ['View', 'Edit', 'Mark Passed', 'Mark Returned'],
    ])
@endsection
