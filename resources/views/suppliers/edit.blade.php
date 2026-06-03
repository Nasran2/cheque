@extends('layouts.app')

@section('title', 'Edit Supplier - Cheque Management System')
@section('page_title', 'Edit Supplier')
@section('mobile_title', 'Edit Supplier')

@section('content')
    <div class="mx-auto max-w-5xl rounded-3xl bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-xl font-extrabold text-navy">Edit Supplier</h3>
            <a href="{{ route('suppliers.show', $supplier) }}" class="text-sm font-bold text-primary">View</a>
        </div>

        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            @include('suppliers.partials.form')
        </form>
    </div>
@endsection
