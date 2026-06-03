@extends('layouts.app')

@php
    use App\Support\Currency;
@endphp

@section('title', 'Customer Details - Cheque Management System')
@section('page_title', 'Customer Details')
@section('mobile_title', 'Customer')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="rounded-3xl bg-white p-5 shadow-soft sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-2xl font-extrabold text-navy">{{ $customer->name }}</h3>
                    <p class="text-slate-500">{{ $customer->business_name ?: 'No business name' }}</p>
                    <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $customer->status === 'active' ? 'bg-green-100 text-success' : 'bg-red-100 text-danger' }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-2xl bg-primary px-5 py-3 text-center text-sm font-bold text-white">Edit Customer</a>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-3xl bg-white p-5 shadow-soft">
                <h4 class="mb-4 font-extrabold text-navy">Balance Details</h4>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Current Balance</dt><dd class="font-bold text-primary">{{ Currency::formatLkr($customer->current_balance) }}</dd></div>
                </dl>
            </div>

            <div class="rounded-3xl bg-white p-5 shadow-soft">
                <h4 class="mb-4 font-extrabold text-navy">Contact Details</h4>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-bold">{{ $customer->phone ?: '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Second Phone</dt><dd class="font-bold">{{ $customer->phone_2 ?: '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-bold">{{ $customer->email ?: '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">NIC</dt><dd class="font-bold">{{ $customer->nic ?: '-' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-3xl bg-white p-5 shadow-soft">
                <h4 class="mb-3 font-extrabold text-navy">Address</h4>
                <p class="text-sm text-slate-600">{{ $customer->address ?: '-' }}</p>
                <p class="mt-2 text-sm font-bold text-navy">{{ $customer->city ?: '-' }}</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft">
                <h4 class="mb-3 font-extrabold text-navy">Notes</h4>
                <p class="text-sm text-slate-600">{{ $customer->notes ?: '-' }}</p>
            </div>
        </div>
    </div>
@endsection
