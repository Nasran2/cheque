@extends('layouts.app')

@section('title', 'Settings - Cheque Management System')
@section('page_title', 'Settings')
@section('mobile_title', 'Settings')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" class="grid gap-6 lg:grid-cols-[280px_1fr]">
        @csrf
        <aside class="hidden rounded-3xl bg-white p-4 shadow-soft lg:block">
            <h3 class="px-3 py-2 text-sm font-extrabold text-navy">Settings Sections</h3>
            @foreach ($defaults as $group => $items)
                <a href="#{{ $group }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">{{ str($group)->replace('_', ' ')->title() }}</a>
            @endforeach
            <a href="{{ route('settings.sms') }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                <i class="fa-solid fa-tower-broadcast mr-2 text-primary"></i>SMS Settings
            </a>
            <a href="{{ route('settings.sms.templates') }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                <i class="fa-solid fa-message mr-2 text-teal"></i>SMS Templates
            </a>
        </aside>

        <div class="space-y-5">
            <div class="sticky top-20 z-20 hidden justify-end gap-3 lg:flex">
                <button class="rounded-2xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20">Save Settings</button>
            </div>

            @foreach ($defaults as $group => $items)
                <section id="{{ $group }}" class="rounded-3xl bg-white p-5 shadow-soft">
                    <h3 class="text-lg font-extrabold text-navy">{{ str($group)->replace('_', ' ')->title() }}</h3>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($items as $key => $meta)
                            <div class="{{ $meta['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                                <label class="mb-2 block text-sm font-bold text-navy">{{ $meta['label'] }}</label>
                                @if ($meta['type'] === 'boolean')
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <label class="inline-flex cursor-pointer items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                        <input type="checkbox" name="{{ $key }}" value="1" @checked(($settings[$key] ?? '0') == '1') class="h-5 w-5 rounded text-primary">
                                        <span class="text-sm font-semibold text-slate-700">Enabled</span>
                                    </label>
                                @elseif ($meta['type'] === 'textarea')
                                    <textarea name="{{ $key }}" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">{{ $settings[$key] ?? '' }}</textarea>
                                @else
                                    <input type="{{ $meta['type'] }}" name="{{ $key }}" value="{{ $settings[$key] ?? '' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <button class="sticky bottom-24 w-full rounded-2xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 lg:static">Save Settings</button>
        </div>
    </form>
@endsection
