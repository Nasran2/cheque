@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $colorMap = [
        'primary'   => ['icon' => 'bg-primary/10 text-primary',     'text' => 'text-primary',    'btn' => 'bg-primary text-white hover:bg-blue-700'],
        'warning'   => ['icon' => 'bg-orange-100 text-orange-600',  'text' => 'text-orange-600', 'btn' => 'bg-orange-500 text-white hover:bg-orange-600'],
        'success'   => ['icon' => 'bg-emerald-100 text-emerald-600','text' => 'text-emerald-600','btn' => 'bg-emerald-500 text-white hover:bg-emerald-600'],
        'danger'    => ['icon' => 'bg-red-100 text-red-600',        'text' => 'text-red-600',    'btn' => 'bg-red-500 text-white hover:bg-red-600'],
        'teal'      => ['icon' => 'bg-teal/10 text-teal',           'text' => 'text-teal',       'btn' => 'bg-teal text-white hover:bg-cyan-600'],
        'purplePay' => ['icon' => 'bg-purplePay/10 text-purplePay', 'text' => 'text-purplePay',  'btn' => 'bg-purplePay text-white hover:bg-purple-700'],
        'navy'      => ['icon' => 'bg-navy/10 text-navy',           'text' => 'text-navy',       'btn' => 'bg-navy text-white hover:bg-slate-800'],
    ];

    $c = $colorMap[$meta['color']] ?? $colorMap['primary'];

    $quickPeriods = [
        'today'      => 'Today',
        'this_week'  => 'This Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year'  => 'This Year',
    ];

    $currentPeriod = request('period', '');
    $hasCustomDate = request()->filled('from_date') || request()->filled('to_date');
@endphp

@section('title', $meta['title'] . ' - Cheque Management System')
@section('page_title', $meta['title'])
@section('mobile_title', 'Report')

@section('content')

    {{-- ── Breadcrumb + Actions ────────────────────────────────────────── --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow-soft hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left text-navy"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $c['icon'] }}">
                        <i class="{{ $meta['icon'] }} text-sm"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-navy">{{ $meta['title'] }}</h3>
                </div>
                <p class="mt-0.5 text-xs text-slate-400">
                    <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                    <i class="fa-solid fa-angle-right mx-1 text-[10px]"></i>{{ $meta['title'] }}
                </p>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reports.export.pdf', [$reportKey] + request()->query()) }}" target="_blank"
                class="flex items-center gap-2 rounded-2xl bg-red-50 px-4 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-500 hover:text-white">
                <i class="fa-solid fa-print"></i>Print / PDF
            </a>
            <a href="{{ route('reports.export.excel', [$reportKey] + request()->query()) }}"
                class="flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-500 hover:text-white">
                <i class="fa-solid fa-file-csv"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- ── Quick Period Chips ───────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('reports.' . $reportKey) }}"
            class="rounded-full px-4 py-2 text-xs font-bold transition
                {{ !$currentPeriod && !$hasCustomDate ? $c['btn'] . ' shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 shadow-soft' }}">
            All Time
        </a>
        @foreach ($quickPeriods as $val => $label)
            <a href="{{ route('reports.' . $reportKey, ['period' => $val]) }}"
                class="rounded-full px-4 py-2 text-xs font-bold transition
                    {{ $currentPeriod === $val && !$hasCustomDate ? $c['btn'] . ' shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 shadow-soft' }}">
                {{ $label }}
            </a>
        @endforeach
        <span class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-400 shadow-soft">
            Custom →
        </span>
    </div>

    {{-- ── Advanced Filter Bar ──────────────────────────────────────────── --}}
    <form method="GET" id="filterForm"
        class="mb-5 rounded-3xl bg-white p-4 shadow-soft">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">Cheque Type</label>
                <select name="cheque_type" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <option value="">All Types</option>
                    <option value="{{ Cheque::TYPE_CUSTOMER_RECEIVED }}" @selected(request('cheque_type') === Cheque::TYPE_CUSTOMER_RECEIVED)>Customer Received</option>
                    <option value="{{ Cheque::TYPE_OWN_ISSUED }}" @selected(request('cheque_type') === Cheque::TYPE_OWN_ISSUED)>Own Issued</option>
                    <option value="{{ Cheque::TYPE_TRANSFER }}" @selected(request('cheque_type') === Cheque::TYPE_TRANSFER)>Customer Cheque Given to Supplier</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">Bank</label>
                <select name="bank" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <option value="">All Banks</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank }}" @selected(request('bank') === $bank)>{{ $bank }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">Cheque No</label>
                <input type="text" name="cheque_no" value="{{ request('cheque_no') }}" placeholder="Search..."
                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">Amount Range</label>
                <div class="flex gap-1">
                    <input type="number" name="amount_min" value="{{ request('amount_min') }}" placeholder="Min"
                        class="w-1/2 rounded-2xl border border-slate-200 px-2 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <input type="number" name="amount_max" value="{{ request('amount_max') }}" placeholder="Max"
                        class="w-1/2 rounded-2xl border border-slate-200 px-2 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit"
                class="rounded-2xl {{ $c['btn'] }} px-6 py-2.5 text-sm font-bold shadow-sm transition">
                <i class="fa-solid fa-magnifying-glass mr-2"></i>Apply Filters
            </button>
            <a href="{{ route('reports.' . $reportKey) }}"
                class="rounded-2xl bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                <i class="fa-solid fa-rotate-right mr-1"></i>Reset
            </a>
            @if (request()->filled('from_date') || request()->filled('to_date') || request()->filled('cheque_type') || request()->filled('bank') || request()->filled('cheque_no'))
                <span class="flex items-center gap-1 rounded-2xl bg-primary/10 px-3 py-2 text-xs font-bold text-primary">
                    <i class="fa-solid fa-filter"></i> Filters active
                </span>
            @endif
        </div>
    </form>

    {{-- ── Summary Cards ────────────────────────────────────────────────── --}}
    <div class="mb-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total Cheques</p>
            <h4 class="mt-2 text-3xl font-extrabold text-navy">{{ number_format($totalCount) }}</h4>
            <p class="mt-1 text-xs {{ $c['text'] }} font-bold">Records found</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total Amount</p>
            <h4 class="mt-2 text-xl font-extrabold {{ $c['text'] }}">{{ Currency::formatLkr($totalAmount) }}</h4>
            <p class="mt-1 text-xs text-slate-400 font-bold">Sri Lankan Rupees</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Customer Received</p>
            <h4 class="mt-2 text-xl font-extrabold text-teal">{{ Currency::formatLkr($customerAmount) }}</h4>
            <p class="mt-1 text-xs text-teal font-bold">Inflow</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Own Issued</p>
            <h4 class="mt-2 text-xl font-extrabold text-purplePay">{{ Currency::formatLkr($ownAmount) }}</h4>
            <p class="mt-1 text-xs text-purplePay font-bold">Outflow</p>
        </div>
    </div>
    
    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Pending Amount</p>
            <h4 class="mt-2 text-xl font-extrabold text-warning">{{ Currency::formatLkr($pendingAmount) }}</h4>
            <p class="mt-1 text-xs text-warning font-bold">Awaiting Clearance</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Passed Amount</p>
            <h4 class="mt-2 text-xl font-extrabold text-success">{{ Currency::formatLkr($passedAmount) }}</h4>
            <p class="mt-1 text-xs text-success font-bold">Cleared successfully</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft col-span-2 lg:col-span-1">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Returned Amount</p>
            <h4 class="mt-2 text-xl font-extrabold text-danger">{{ Currency::formatLkr($returnedAmount) }}</h4>
            <p class="mt-1 text-xs text-danger font-bold">Bounced / Returned</p>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- BANK-WISE REPORT                                                     --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @if ($reportKey === 'bank-wise' && $bankGroups)

        <div class="space-y-4">
            @foreach ($bankGroups as $bankName => $group)
                <div class="overflow-hidden rounded-3xl bg-white shadow-soft">
                    <div class="flex items-center justify-between bg-slate-50 px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purplePay/10">
                                <i class="fa-solid fa-building-columns text-purplePay text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-navy">{{ $bankName }}</h4>
                                <p class="text-xs text-slate-500">{{ $group['count'] }} cheque(s)</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-extrabold text-purplePay">{{ Currency::formatLkr($group['amount']) }}</p>
                            <p class="text-xs text-slate-400">total value</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3 border-b border-slate-100 bg-white px-5 py-4">
                        <div class="rounded-2xl bg-warning/10 px-4 py-3">
                            <p class="text-xs text-warning">Pending</p>
                            <p class="text-base font-extrabold text-warning">{{ Currency::formatLkr($group['pending']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-success/10 px-4 py-3">
                            <p class="text-xs text-success">Passed</p>
                            <p class="text-base font-extrabold text-success">{{ Currency::formatLkr($group['passed']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-danger/10 px-4 py-3">
                            <p class="text-xs text-danger">Returned</p>
                            <p class="text-base font-extrabold text-danger">{{ Currency::formatLkr($group['returned']) }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[800px] w-full text-left text-sm">
                            <thead class="bg-slate-50/50 text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-bold">Cheque No</th>
                                    <th class="py-3 text-xs font-bold">Type</th>
                                    <th class="py-3 text-xs font-bold">Customer / Supplier</th>
                                    <th class="py-3 text-xs font-bold">Date</th>
                                    <th class="py-3 text-xs font-bold text-right pr-5">Amount</th>
                                    <th class="py-3 text-xs font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($group['cheques'] as $cheque)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                                        <td class="py-3 pr-4">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::typeBadgeClass($cheque->cheque_type) }}">
                                                {{ ChequePresentation::typeLabel($cheque->cheque_type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                                <span class="block font-bold">From Customer: {{ $cheque->customer?->name ?? '—' }}</span>
                                                <span class="block text-xs text-slate-400">To Supplier: {{ $cheque->supplier?->name ?? '—' }}</span>
                                            @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                                <span class="block font-bold">{{ $cheque->customer?->name ?? '—' }}</span>
                                                <span class="block text-xs text-amber-600 font-semibold">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                            @else
                                                {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">{{ $cheque->cheque_date?->format('d M Y') }}</td>
                                        <td class="py-3 pr-5 text-right font-bold text-purplePay">{{ Currency::formatLkr($cheque->amount) }}</td>
                                        <td class="py-3">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                                {{ ChequePresentation::statusLabel($cheque->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- CUSTOMER / SUPPLIER WISE REPORT                                      --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @elseif (in_array($reportKey, ['customer-wise', 'supplier-wise'], true) && $partyGroups)

        <div class="space-y-4">
            @foreach ($partyGroups as $partyName => $group)
                <div class="overflow-hidden rounded-3xl bg-white shadow-soft">
                    <div class="flex items-center justify-between bg-slate-50 px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $reportKey === 'customer-wise' ? 'bg-teal/10' : 'bg-purplePay/10' }}">
                                <i class="fa-solid {{ $reportKey === 'customer-wise' ? 'fa-users text-teal' : 'fa-truck-field text-purplePay' }} text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-navy">{{ $partyName }}</h4>
                                <p class="text-xs text-slate-500">{{ $group['count'] }} cheque(s)</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-extrabold {{ $reportKey === 'customer-wise' ? 'text-teal' : 'text-purplePay' }}">{{ Currency::formatLkr($group['amount']) }}</p>
                            <p class="text-xs text-slate-400">total value</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-b border-slate-100 bg-white px-5 py-4 md:grid-cols-5">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-400">Cheque Count</p>
                            <p class="text-base font-extrabold text-navy">{{ number_format($group['count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-teal/10 px-4 py-3">
                            <p class="text-xs text-teal">Customer Received</p>
                            <p class="text-base font-extrabold text-teal">{{ Currency::formatLkr($group['customer']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-purplePay/10 px-4 py-3">
                            <p class="text-xs text-purplePay">Own Issued</p>
                            <p class="text-base font-extrabold text-purplePay">{{ Currency::formatLkr($group['own']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-warning/10 px-4 py-3">
                            <p class="text-xs text-warning">Pending</p>
                            <p class="text-base font-extrabold text-warning">{{ Currency::formatLkr($group['pending']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-success/10 px-4 py-3">
                            <p class="text-xs text-success">Passed</p>
                            <p class="text-base font-extrabold text-success">{{ Currency::formatLkr($group['passed']) }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[900px] w-full text-left text-sm">
                            <thead class="bg-slate-50/50 text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-bold">Cheque No</th>
                                    <th class="py-3 text-xs font-bold">Type</th>
                                    <th class="py-3 text-xs font-bold">Customer</th>
                                    <th class="py-3 text-xs font-bold">Supplier</th>
                                    <th class="py-3 text-xs font-bold">Bank</th>
                                    <th class="py-3 text-xs font-bold">Date</th>
                                    <th class="py-3 text-xs font-bold text-right pr-5">Amount</th>
                                    <th class="py-3 text-xs font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($group['cheques'] as $cheque)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                                        <td class="py-3 pr-4">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::typeBadgeClass($cheque->cheque_type) }}">
                                                {{ $cheque->isOwnIssued() && $cheque->supplier_cheque_mode === 'received_customer_cheque' ? 'Customer Cheque' : ChequePresentation::typeLabel($cheque->cheque_type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4 font-semibold text-slate-700">{{ $cheque->customer?->name ?? $cheque->originalCustomer?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4 font-semibold text-slate-700">{{ $cheque->supplier?->name ?? $cheque->givenToSupplier?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4">{{ $cheque->bank_name }}</td>
                                        <td class="py-3 pr-4">{{ $cheque->cheque_date?->format('d M Y') }}</td>
                                        <td class="py-3 pr-5 text-right font-bold {{ $reportKey === 'customer-wise' ? 'text-teal' : 'text-purplePay' }}">{{ Currency::formatLkr($cheque->amount) }}</td>
                                        <td class="py-3">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                                {{ ChequePresentation::statusLabel($cheque->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- MONTHLY SUMMARY REPORT                                               --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @elseif ($reportKey === 'monthly-summary' && $monthGroups)

        <div class="space-y-4">
            @foreach ($monthGroups as $yearMonth => $group)
                <div class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="text-base font-extrabold text-navy">{{ $group['label'] }}</h4>
                        <span class="text-sm text-slate-400">{{ $group['count'] }} cheque(s)</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4 md:grid-cols-5">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-400">Total</p>
                            <p class="text-base font-extrabold text-navy">{{ Currency::formatLkr($group['amount']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-teal/10 px-4 py-3">
                            <p class="text-xs text-teal">Customer Received</p>
                            <p class="text-base font-extrabold text-teal">{{ Currency::formatLkr($group['customer']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-purplePay/10 px-4 py-3">
                            <p class="text-xs text-purplePay">Own Issued</p>
                            <p class="text-base font-extrabold text-purplePay">{{ Currency::formatLkr($group['own']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-warning/10 px-4 py-3">
                            <p class="text-xs text-warning">Pending</p>
                            <p class="text-base font-extrabold text-warning">{{ Currency::formatLkr($group['pending']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-success/10 px-4 py-3">
                            <p class="text-xs text-success">Passed</p>
                            <p class="text-base font-extrabold text-success">{{ Currency::formatLkr($group['passed']) }}</p>
                        </div>
                    </div>
                    {{-- Progress bar --}}
                    @php
                        $maxAmount = $monthGroups->max('amount');
                        $pct = $maxAmount > 0 ? ($group['amount'] / $maxAmount) * 100 : 0;
                    @endphp
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-primary transition-all" style="width: {{ round($pct) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- STANDARD CHEQUE TABLE (all other reports)                            --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @else

        {{-- Mobile cards --}}
        <div class="space-y-3 lg:hidden">
            @forelse ($cheques as $cheque)
                <div class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Cheque No</p>
                            <h4 class="text-lg font-extrabold text-navy">{{ $cheque->cheque_no }}</h4>
                            <p class="text-sm text-slate-500">
                                @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                    <span class="block">From Customer: <strong class="text-slate-700">{{ $cheque->customer?->name ?? '—' }}</strong></span>
                                    <span class="block">To Supplier: <strong class="text-slate-700">{{ $cheque->supplier?->name ?? '—' }}</strong></span>
                                @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                    <span class="block">Customer: <strong class="text-slate-700">{{ $cheque->customer?->name ?? '—' }}</strong></span>
                                    <span class="block text-xs text-amber-600 font-semibold">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                @else
                                    {{ $cheque->isCustomerReceived() ? 'Customer' : 'Supplier' }}:
                                    <span class="font-semibold text-slate-700">{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '—' }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                            {{ ChequePresentation::statusLabel($cheque->status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><p class="text-xs text-slate-400">Bank</p><p class="font-semibold text-navy">{{ $cheque->bank_name }}</p></div>
                        <div><p class="text-xs text-slate-400">Date</p><p class="font-semibold text-navy">{{ $cheque->cheque_date?->format('d M Y') }}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Amount</p><p class="text-lg font-extrabold {{ $c['text'] }}">{{ Currency::formatLkr($cheque->amount) }}</p></div>
                    </div>
                </div>
            @empty
                <p class="rounded-3xl bg-white p-8 text-center text-sm text-slate-400 shadow-soft">No records found for the selected filters.</p>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto rounded-3xl bg-white shadow-soft lg:block">
            <table class="min-w-[1000px] w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-4 text-xs font-bold">#</th>
                        <th class="py-4 text-xs font-bold">Cheque No</th>
                        <th class="py-4 text-xs font-bold">Type</th>
                        <th class="py-4 text-xs font-bold">Customer / Supplier</th>
                        <th class="py-4 text-xs font-bold">Bank</th>
                        <th class="py-4 text-xs font-bold">Branch</th>
                        <th class="py-4 text-xs font-bold">Cheque Date</th>
                        <th class="py-4 text-xs font-bold text-right pr-5">Amount</th>
                        <th class="py-4 text-xs font-bold">Status</th>
                        <th class="py-4 pr-5 text-xs font-bold">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cheques as $i => $cheque)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 text-xs text-slate-400">{{ $cheques->firstItem() + $i }}</td>
                            <td class="py-4 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                            <td class="py-4 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::typeBadgeClass($cheque->cheque_type) }}">
                                    {{ ChequePresentation::typeLabel($cheque->cheque_type) }}
                                </span>
                            </td>
                            <td class="py-4 pr-4 font-medium text-slate-700">
                                @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                    <span class="block font-bold">From Customer: {{ $cheque->customer?->name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-400">To Supplier: {{ $cheque->supplier?->name ?? '—' }}</span>
                                @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                    <span class="block font-bold">{{ $cheque->customer?->name ?? '—' }}</span>
                                    <span class="block text-xs text-amber-600 font-semibold">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                @else
                                    {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-slate-600">{{ $cheque->bank_name }}</td>
                            <td class="py-4 pr-4 text-slate-500">{{ $cheque->branch_name ?: '—' }}</td>
                            <td class="py-4 pr-4 text-slate-600">{{ $cheque->cheque_date?->format('d M Y') }}</td>
                            <td class="py-4 pr-5 text-right font-bold {{ $c['text'] }}">{{ Currency::formatLkr($cheque->amount) }}</td>
                            <td class="py-4 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                    {{ ChequePresentation::statusLabel($cheque->status) }}
                                </span>
                            </td>
                            <td class="py-4 pr-5 text-xs text-slate-400">{{ \Str::limit($cheque->notes ?? '—', 30) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-inbox mb-2 block text-3xl"></i>
                                No records found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($cheques->count())
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="7" class="px-5 py-4 text-sm font-bold text-navy">
                                Showing {{ $cheques->firstItem() }}–{{ $cheques->lastItem() }} of {{ number_format($totalCount) }} records
                            </td>
                            <td class="py-4 pr-5 text-right text-base font-extrabold {{ $c['text'] }}">{{ Currency::formatLkr($totalAmount) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        @if ($cheques->hasPages())
            <div class="mt-5">{{ $cheques->links() }}</div>
        @endif

    @endif

    {{-- ── Report Navigator ─────────────────────────────────────────────── --}}
    <div class="mt-8 rounded-3xl bg-white p-4 shadow-soft">
        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-400">Jump to another report</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($reports as $key => $rep)
                <a href="{{ route($rep['route']) }}"
                    class="rounded-2xl px-3 py-2 text-xs font-bold transition
                        {{ $key === $reportKey ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $rep['title'] }}
                </a>
            @endforeach
        </div>
    </div>

@endsection
