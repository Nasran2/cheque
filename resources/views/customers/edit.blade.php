@extends('layouts.app')

@section('title', 'Edit Customer - Cheque Management System')
@section('page_title', 'Edit Customer')
@section('mobile_title', 'Edit Customer')

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-xl font-extrabold text-navy">Edit Customer</h3>
            <a href="{{ route('customers.show', $customer) }}" class="text-sm font-bold text-primary">View</a>
        </div>

        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('customers.partials.form')
        </form>
    </div>
@endsection
