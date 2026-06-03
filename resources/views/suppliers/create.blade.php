@extends('layouts.app')

@section('title', 'Add Supplier - Cheque Management System')
@section('page_title', 'Add Supplier')
@section('mobile_title', 'Add Supplier')

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-xl font-extrabold text-navy">Create Supplier</h3>
            <a href="{{ route('suppliers.index') }}" class="text-sm font-bold text-primary">Back</a>
        </div>

        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            @include('suppliers.partials.form')
        </form>
    </div>
@endsection
