@extends('layouts.app')

@section('title', 'Reports - Cheque Management System')
@section('page_title', 'Reports')
@section('mobile_title', 'Reports')

@php
    $colorMap = [
        'primary'   => ['bg' => 'bg-primary/10',   'text' => 'text-primary',   'btn' => 'bg-primary text-white',    'ring' => 'ring-primary/20'],
        'warning'   => ['bg' => 'bg-orange-100',    'text' => 'text-orange-600','btn' => 'bg-orange-500 text-white', 'ring' => 'ring-orange-200'],
        'success'   => ['bg' => 'bg-emerald-100',   'text' => 'text-emerald-600','btn'=> 'bg-emerald-500 text-white','ring'=> 'ring-emerald-200'],
        'danger'    => ['bg' => 'bg-red-100',       'text' => 'text-red-600',   'btn' => 'bg-red-500 text-white',   'ring' => 'ring-red-200'],
        'teal'      => ['bg' => 'bg-teal/10',       'text' => 'text-teal',      'btn' => 'bg-teal text-white',      'ring' => 'ring-teal/20'],
        'purplePay' => ['bg' => 'bg-purplePay/10',  'text' => 'text-purplePay', 'btn' => 'bg-purplePay text-white', 'ring' => 'ring-purplePay/20'],
        'navy'      => ['bg' => 'bg-navy/10',       'text' => 'text-navy',      'btn' => 'bg-navy text-white',      'ring' => 'ring-navy/20'],
    ];
@endphp

@section('content')

    {{-- ── Page Header ────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-2xl font-extrabold text-navy">Report Centre</h3>
            <p class="mt-1 text-sm text-slate-500">Generate, filter, print and export professional cheque reports.</p>
        </div>
        <div class="flex items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-soft">
            <i class="fa-solid fa-calendar-days text-primary"></i>
            <span class="text-sm font-bold text-navy">{{ now()->format('d M Y') }}</span>
        </div>
    </div>

    {{-- ── Summary Strip ───────────────────────────────────────────────── --}}
    @php
        $totalAll     = \App\Models\Cheque::count();
        $totalAmount  = \App\Models\Cheque::sum('amount');
        $totalPending = \App\Models\Cheque::where('status', 'pending')->count();
        $totalPassed  = \App\Models\Cheque::where('status', 'passed')->sum('amount');
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-3xl bg-gradient-to-br from-primary to-blue-700 p-5 text-white shadow-lg shadow-primary/20">
            <p class="text-xs font-bold uppercase tracking-wide opacity-80">Total Cheques</p>
            <h4 class="mt-2 text-3xl font-extrabold">{{ number_format($totalAll) }}</h4>
            <p class="mt-1 text-xs opacity-70">All records</p>
        </div>
        <div class="rounded-3xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg shadow-emerald-500/20">
            <p class="text-xs font-bold uppercase tracking-wide opacity-80">Total Value</p>
            <h4 class="mt-2 text-2xl font-extrabold">{{ \App\Support\Currency::formatLkr($totalAmount) }}</h4>
            <p class="mt-1 text-xs opacity-70">All cheques</p>
        </div>
        <div class="rounded-3xl bg-gradient-to-br from-orange-400 to-orange-600 p-5 text-white shadow-lg shadow-orange-400/20">
            <p class="text-xs font-bold uppercase tracking-wide opacity-80">Pending</p>
            <h4 class="mt-2 text-3xl font-extrabold">{{ number_format($totalPending) }}</h4>
            <p class="mt-1 text-xs opacity-70">Awaiting action</p>
        </div>
        <div class="rounded-3xl bg-gradient-to-br from-teal to-cyan-600 p-5 text-white shadow-lg shadow-teal/20">
            <p class="text-xs font-bold uppercase tracking-wide opacity-80">Passed Amount</p>
            <h4 class="mt-2 text-2xl font-extrabold">{{ \App\Support\Currency::formatLkr($totalPassed) }}</h4>
            <p class="mt-1 text-xs opacity-70">Successfully cleared</p>
        </div>
    </div>

    {{-- ── Report Cards Grid ───────────────────────────────────────────── --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach ($reports as $key => $report)
            @php $c = $colorMap[$report['color']] ?? $colorMap['primary']; @endphp
            <div class="group flex flex-col rounded-3xl bg-white p-6 shadow-soft ring-1 ring-slate-100 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-transparent">

                {{-- Icon + Count --}}
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $c['bg'] }}">
                        <i class="{{ $report['icon'] }} text-xl {{ $c['text'] }}"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-extrabold text-navy">{{ number_format($report['count']) }}</p>
                        <p class="text-xs text-slate-400">cheques</p>
                    </div>
                </div>

                {{-- Title & Description --}}
                <h4 class="text-base font-extrabold text-navy">{{ $report['title'] }}</h4>
                <p class="mt-1 flex-1 text-xs leading-relaxed text-slate-500">{{ $report['description'] }}</p>

                {{-- Amount --}}
                <div class="my-4 rounded-2xl {{ $c['bg'] }} px-3 py-2">
                    <p class="text-xs font-bold {{ $c['text'] }}">Total: {{ \App\Support\Currency::formatLkr($report['amount']) }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route($report['route']) }}"
                        class="flex flex-1 items-center justify-center gap-2 rounded-2xl {{ $c['btn'] }} px-4 py-2.5 text-xs font-bold shadow-sm transition hover:opacity-90">
                        <i class="fa-solid fa-table-list"></i> View Report
                    </a>
                    <a href="{{ route('reports.export.pdf', $key) }}" target="_blank"
                        class="flex items-center gap-1.5 rounded-2xl bg-slate-100 px-3 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-200">
                        <i class="fa-solid fa-file-pdf text-red-500"></i> PDF
                    </a>
                    <a href="{{ route('reports.export.excel', $key) }}"
                        class="flex items-center gap-1.5 rounded-2xl bg-emerald-50 px-3 py-2.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                        <i class="fa-solid fa-file-csv text-emerald-600"></i> CSV
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── Footer note ─────────────────────────────────────────────────── --}}
    <p class="mt-8 text-center text-xs text-slate-400">
        <i class="fa-solid fa-code mr-1 text-teal"></i>
        Cheque Management System — Powered by <strong class="text-slate-600">Twinsofte.com</strong>
    </p>

@endsection
