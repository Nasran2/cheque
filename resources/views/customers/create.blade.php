@extends('layouts.app')

@section('title', 'Add Customer - Cheque Management System')
@section('page_title', 'Add Customer')
@section('mobile_title', 'Add Customer')

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-xl font-extrabold text-navy">Create Customer</h3>
            <a href="{{ route('customers.index') }}" class="text-sm font-bold text-primary">Back</a>
        </div>

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers.partials.form')
        </form>
    </div>
@endsection
