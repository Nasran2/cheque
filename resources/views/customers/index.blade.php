@extends('layouts.app')

@php
    use App\Support\Currency;
@endphp

@section('title', 'Customers - Cheque Management System')
@section('page_title', 'Customers')
@section('mobile_title', 'Customers')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-extrabold text-navy">Customer Management</h3>
            <p class="text-sm text-slate-500">Create and manage customers before receiving cheques.</p>
        </div>
        <a href="{{ route('customers.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20">
            <i class="fa-solid fa-plus"></i>
            Add Customer
        </a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('customers.index') }}" class="mb-5 grid gap-3 rounded-3xl bg-white p-4 shadow-soft md:grid-cols-[1fr_180px_auto]">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, business, phone, email, city..." class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
        <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white">Search</button>
    </form>

    <div class="space-y-3 lg:hidden">
        @forelse ($customers as $customer)
            <div class="rounded-3xl bg-white p-5 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="font-extrabold text-navy">{{ $customer->name }}</h4>
                        <p class="text-sm text-slate-500">{{ $customer->business_name ?: 'No business name' }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $customer->phone ?: 'No phone' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $customer->status === 'active' ? 'bg-green-100 text-success' : 'bg-red-100 text-danger' }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm text-slate-500">Current Balance</span>
                    <strong class="text-primary">{{ Currency::formatLkr($customer->current_balance) }}</strong>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3">
                    <div>
                        <span class="block text-xs font-semibold text-slate-400">Cheques</span>
                        <strong class="text-sm text-navy">{{ number_format($customer->cheques_count) }}</strong>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs font-semibold text-slate-400">Cheque Total</span>
                        <strong class="text-sm text-primary">{{ Currency::formatLkr($customer->cheque_total_amount ?? 0) }}</strong>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <a href="{{ route('cheques.index', ['customer_id' => $customer->id]) }}" class="rounded-xl bg-teal/10 px-3 py-2 text-center text-xs font-bold text-teal">Cheques</a>
                    <a href="{{ route('customers.show', $customer) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-center text-xs font-bold text-slate-700">View</a>
                    <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl bg-primary px-3 py-2 text-center text-xs font-bold text-white">Edit</a>
                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="rounded-3xl bg-white p-6 text-center text-sm text-slate-500 shadow-soft">No customers found.</p>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-soft lg:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-4">Name</th>
                    <th>Business</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Cheques</th>
                    <th>Cheque Total</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-5 py-4 font-bold text-navy">{{ $customer->name }}</td>
                        <td>{{ $customer->business_name ?: '-' }}</td>
                        <td>{{ $customer->phone ?: '-' }}</td>
                        <td>{{ $customer->email ?: '-' }}</td>
                        <td>
                            <a href="{{ route('cheques.index', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-extrabold text-primary transition hover:bg-primary hover:text-white">
                                <i class="fa-solid fa-money-check"></i>
                                {{ number_format($customer->cheques_count) }}
                            </a>
                        </td>
                        <td class="font-bold text-primary">{{ Currency::formatLkr($customer->cheque_total_amount ?? 0) }}</td>
                        <td class="font-bold text-primary">{{ Currency::formatLkr($customer->current_balance) }}</td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $customer->status === 'active' ? 'bg-green-100 text-success' : 'bg-red-100 text-danger' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </td>
                        <td class="pr-5">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('cheques.index', ['customer_id' => $customer->id]) }}" class="rounded-xl bg-teal/10 px-3 py-2 text-xs font-bold text-teal transition hover:bg-teal hover:text-white">Cheques</a>
                                <a href="{{ route('customers.show', $customer) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700">View</a>
                                <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl bg-primary px-3 py-2 text-xs font-bold text-white">Edit</a>
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-8 text-center text-slate-500">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $customers->links() }}
    </div>
@endsection
