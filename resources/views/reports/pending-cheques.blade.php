@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;
@endphp

@section('title', $title . ' - Cheque Management System')
@section('page_title', $title)
@section('mobile_title', 'Report')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-extrabold text-navy">{{ $title }}</h3>
            <p class="text-sm text-slate-500">Filtered report with export actions and Rs totals.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.export.pdf', [$reportType] + request()->query()) }}" class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-danger">Export PDF</a>
            <a href="{{ route('reports.export.excel', [$reportType] + request()->query()) }}" class="rounded-2xl bg-green-50 px-4 py-3 text-sm font-bold text-success">Export Excel</a>
            <button onclick="window.print()" class="rounded-2xl bg-primary px-4 py-3 text-sm font-bold text-white">Print</button>
        </div>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-3xl bg-white p-4 shadow-soft md:grid-cols-2 xl:grid-cols-4">
        <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        <select name="cheque_type" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All Types</option>
            <option value="{{ Cheque::TYPE_CUSTOMER_RECEIVED }}" @selected(request('cheque_type') === Cheque::TYPE_CUSTOMER_RECEIVED)>Customer Received</option>
            <option value="{{ Cheque::TYPE_OWN_ISSUED }}" @selected(request('cheque_type') === Cheque::TYPE_OWN_ISSUED)>Own Issued</option>
        </select>
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All Status</option>
            @foreach ([Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_PASSED, Cheque::STATUS_RETURNED, Cheque::STATUS_HOLD, Cheque::STATUS_CANCELLED] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ChequePresentation::statusLabel($status) }}</option>
            @endforeach
        </select>
        <select name="bank" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All Banks</option>
            @foreach ($banks as $bank)
                <option value="{{ $bank }}" @selected(request('bank') === $bank)>{{ $bank }}</option>
            @endforeach
        </select>
        <input name="cheque_no" value="{{ request('cheque_no') }}" placeholder="Cheque number" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        <input name="amount_min" value="{{ request('amount_min') }}" placeholder="Min amount" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        <input name="amount_max" value="{{ request('amount_max') }}" placeholder="Max amount" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white">Search</button>
        <a href="{{ url()->current() }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-700">Reset</a>
    </form>

    <div class="mb-5 rounded-3xl bg-white p-5 shadow-soft">
        <p class="text-sm text-slate-500">Total Amount</p>
        <h4 class="text-2xl font-extrabold text-primary">{{ Currency::formatLkr($totalAmount) }}</h4>
    </div>

    <div class="overflow-x-auto rounded-3xl bg-white shadow-soft">
        <table class="min-w-[980px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-4">Cheque No</th>
                    <th>Cheque Type</th>
                    <th>Customer/Supplier</th>
                    <th>Bank</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($cheques as $cheque)
                    <tr>
                        <td class="px-5 py-4 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                        <td>{{ $cheque->isCustomerReceived() ? 'Customer Received' : 'Own Issued' }}</td>
                        <td>{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '-' }}</td>
                        <td>{{ $cheque->bank_name }}</td>
                        <td>{{ $cheque->branch_name ?: '-' }}</td>
                        <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                        <td class="font-bold text-primary">{{ Currency::formatLkr($cheque->amount) }}</td>
                        <td>{{ ChequePresentation::statusLabel($cheque->status) }}</td>
                        <td>{{ $cheque->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $cheques->links() }}</div>
@endsection
