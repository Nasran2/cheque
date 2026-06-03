@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $calendarChequePayload = $monthCheques->map(function ($items) {
        return $items->map(function ($cheque) {
            $partyLabel = $cheque->cheque_type === Cheque::TYPE_TRANSFER 
                ? 'From/To' 
                : ($cheque->isCustomerReceived() ? 'Customer' : 'Supplier');

            $partyName = $cheque->cheque_type === Cheque::TYPE_TRANSFER 
                ? ($cheque->customer?->name ?? '—') . ' → ' . ($cheque->supplier?->name ?? '—') 
                : ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier 
                    ? ($cheque->customer?->name ?? '—') . ' (Given to Supplier: ' . ($cheque->givenToSupplier?->name ?? '—') . ')' 
                    : ($cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party'));

            return [
                'id' => $cheque->id,
                'cheque_no' => $cheque->cheque_no,
                'party_label' => $partyLabel,
                'party_name' => $partyName,
                'bank_name' => $cheque->bank_name,
                'amount' => Currency::formatLkr($cheque->amount),
                'status' => ChequePresentation::statusLabel($cheque->status),
                'status_class' => ChequePresentation::statusBadgeClass($cheque->status),
                'type' => ChequePresentation::displayTypeLabel($cheque),
                'type_class' => ChequePresentation::displayTypeBadgeClass($cheque),
            ];
        })->values();
    });
@endphp

@section('title', 'Upcoming Cheques - Cheque Management System')
@section('page_title', 'Upcoming Cheques')
@section('mobile_title', 'Upcoming')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-extrabold text-navy">Upcoming Cheques Calendar</h3>
            <p class="text-sm text-slate-500">Calendar view for pending, deposited, and hold cheques.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('cheques.upcoming', ['month' => $month->copy()->subMonth()->format('Y-m'), 'date' => $selectedDate->toDateString()]) }}" class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-navy shadow-soft">
                <i class="fa-solid fa-angle-left"></i>
            </a>
            <a href="{{ route('cheques.upcoming', ['month' => $month->copy()->addMonth()->format('Y-m'), 'date' => $selectedDate->toDateString()]) }}" class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-navy shadow-soft">
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4 2xl:grid-cols-8">
        @foreach ([
            ['Today Cheques', $summary['today_count'], 'fa-regular fa-calendar-check'],
            ['Tomorrow Cheques', $summary['tomorrow_count'], 'fa-regular fa-calendar-plus'],
            ['Next 7 Days', $summary['next_7_count'], 'fa-solid fa-calendar-week'],
            ['Next 30 Days', $summary['next_30_count'], 'fa-regular fa-calendar-days'],
            ['Customer Upcoming', Currency::formatLkr($summary['customer_amount']), 'fa-solid fa-arrow-down'],
            ['Own Upcoming', Currency::formatLkr($summary['own_amount']), 'fa-solid fa-arrow-up'],
            ['Total Upcoming', Currency::formatLkr($summary['total_amount']), 'fa-solid fa-sack-dollar'],
            ['Overdue Amount', Currency::formatLkr($summary['overdue_amount']), 'fa-solid fa-triangle-exclamation'],
        ] as [$label, $value, $icon])
            <div class="rounded-3xl bg-white p-4 shadow-soft">
                <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-primary">
                    <i class="{{ $icon }}"></i>
                </div>
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <h4 class="mt-1 break-words text-xl font-extrabold text-navy">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex gap-2 overflow-x-auto pb-2 lg:hidden">
        @foreach ([
            ['Today', today()],
            ['Tomorrow', today()->addDay()],
            ['This Week', today()->addDays(7)],
        ] as [$label, $date])
            <a href="{{ route('cheques.upcoming', ['month' => $date->format('Y-m'), 'date' => $date->toDateString()]) }}" class="shrink-0 rounded-full bg-primary px-4 py-2 text-sm font-bold text-white">{{ $label }}</a>
        @endforeach
        @foreach ($dateChips as $chip)
            <a href="{{ route('cheques.upcoming', ['month' => $chip->format('Y-m'), 'date' => $chip->toDateString()]) }}" class="shrink-0 rounded-full {{ $selectedDate->isSameDay($chip) ? 'bg-navy text-white' : 'bg-white text-slate-600' }} px-4 py-2 text-sm font-bold shadow-sm">
                {{ $chip->format('d M') }}
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <section class="rounded-3xl bg-white p-4 shadow-soft sm:p-5">
            <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-extrabold text-navy">{{ $month->format('F Y') }}</h4>
                <input type="month" value="{{ $month->format('Y-m') }}" onchange="window.location='{{ route('cheques.upcoming') }}?month='+this.value" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-7 gap-2 text-center text-xs font-bold text-slate-400">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                    <div>{{ $day }}</div>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-7 gap-1.5 sm:gap-2">
                @foreach ($calendarDays as $day)
                    @php
                        $dateKey = $day->toDateString();
                        $items = $monthCheques->get($dateKey, collect());
                        $amount = $items->sum('amount');
                        $isToday = $day->isToday();
                        $isSelected = $day->isSameDay($selectedDate);
                    @endphp
                    <button type="button" data-calendar-date="{{ $dateKey }}" data-calendar-label="{{ $day->format('d M Y') }}" class="group relative flex min-h-[58px] flex-col rounded-2xl border p-1.5 text-left transition sm:min-h-24 sm:p-2 {{ $isSelected ? 'border-primary bg-blue-50 ring-2 ring-primary/10' : 'border-slate-100 bg-slate-50 hover:border-primary/40' }} {{ $day->month !== $month->month ? 'opacity-40' : '' }}">
                        <div class="flex items-start justify-between gap-1">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-extrabold {{ $isToday ? 'bg-primary text-white' : 'text-navy' }}">{{ $day->day }}</span>
                            @if ($items->count())
                                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-100 px-1.5 text-[10px] font-extrabold text-warning">{{ $items->count() }}</span>
                            @endif
                        </div>
                        @if ($items->count())
                            <div class="mt-auto hidden min-w-0 sm:block">
                                <p class="hidden truncate text-xs font-bold text-navy sm:block">{{ $items->count() }} Cheques</p>
                                <p class="truncate text-[9px] font-extrabold leading-tight text-primary sm:text-[11px]">{{ Currency::formatLkr($amount) }}</p>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-0.5 sm:gap-1">
                                @foreach ($items->take(4) as $cheque)
                                    <span class="h-1.5 w-1.5 rounded-full sm:h-2 sm:w-2 {{ $cheque->isCustomerReceived() ? 'bg-teal' : 'bg-purplePay' }}"></span>
                                @endforeach
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl bg-white p-5 shadow-soft">
            <h4 class="text-lg font-extrabold text-navy">{{ $selectedDate->format('d M Y') }} Cheques</h4>
            <div class="mt-3 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Cheques</p>
                    <p class="font-extrabold text-navy">{{ $selectedCheques->count() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500">Total Amount</p>
                    <p class="font-extrabold text-primary">{{ Currency::formatLkr($selectedCheques->sum('amount')) }}</p>
                </div>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($selectedCheques as $cheque)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h5 class="font-extrabold text-navy">{{ $cheque->cheque_no }}</h5>
                                <p class="text-sm text-slate-500">
                                    @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                        From: {{ $cheque->customer?->name ?? '—' }} | To: {{ $cheque->supplier?->name ?? '—' }}
                                    @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                        {{ $cheque->customer?->name ?? '—' }} (Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }})
                                    @else
                                        {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party' }}
                                    @endif
                                </p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">{{ ChequePresentation::statusLabel($cheque->status) }}</span>
                        </div>
                        <div class="mt-3 grid gap-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Type</span><strong>{{ ChequePresentation::displayTypeLabel($cheque) }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Bank</span><strong>{{ $cheque->bank_name }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Amount</span><strong class="text-primary">{{ Currency::formatLkr($cheque->amount) }}</strong></div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('cheques.show', $cheque->id) }}" class="rounded-xl bg-primary px-3 py-2 text-center text-xs font-bold text-white">View</a>
                            <a href="#" class="rounded-xl bg-slate-100 px-3 py-2 text-center text-xs font-bold text-slate-700">Edit</a>
                            <a href="#" class="rounded-xl bg-green-50 px-3 py-2 text-center text-xs font-bold text-success">Mark Passed</a>
                            <a href="#" class="rounded-xl bg-red-50 px-3 py-2 text-center text-xs font-bold text-danger">Mark Returned</a>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No cheques for this date.</p>
                @endforelse
            </div>
        </section>
    </div>

    <a href="{{ route('cheques.create') }}" class="fixed bottom-24 right-5 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 lg:hidden">
        <i class="fa-solid fa-plus"></i>
    </a>

    <div id="calendarChequeModal" class="fixed inset-0 z-[70] hidden items-end bg-slate-950/50 p-0 sm:items-center sm:p-4">
        <div class="max-h-[88vh] w-full overflow-hidden rounded-t-[28px] bg-white shadow-2xl sm:mx-auto sm:max-w-2xl sm:rounded-[28px]">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5">
                <div>
                    <h3 id="calendarModalTitle" class="text-xl font-extrabold text-navy">Date Cheques</h3>
                    <p id="calendarModalSummary" class="mt-1 text-sm text-slate-500">0 cheques</p>
                </div>
                <button type="button" id="calendarModalClose" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="calendarModalBody" class="max-h-[68vh] space-y-3 overflow-y-auto p-5"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const calendarCheques = @json($calendarChequePayload);
        const calendarModal = document.getElementById('calendarChequeModal');
        const calendarModalTitle = document.getElementById('calendarModalTitle');
        const calendarModalSummary = document.getElementById('calendarModalSummary');
        const calendarModalBody = document.getElementById('calendarModalBody');
        const calendarModalClose = document.getElementById('calendarModalClose');

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function openCalendarModal(date, label) {
            const cheques = calendarCheques[date] || [];
            const total = cheques.reduce((sum, cheque) => {
                const numeric = Number(String(cheque.amount).replace(/[^\d.]/g, ''));
                return sum + (Number.isFinite(numeric) ? numeric : 0);
            }, 0);

            calendarModalTitle.textContent = `${label} Cheques`;
            calendarModalSummary.textContent = `${cheques.length} cheque${cheques.length === 1 ? '' : 's'} | Rs ${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            if (!cheques.length) {
                calendarModalBody.innerHTML = '<div class="rounded-2xl bg-slate-50 p-5 text-sm font-semibold text-slate-500">No cheques for this date.</div>';
            } else {
                calendarModalBody.innerHTML = cheques.map((cheque) => `
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Cheque No</p>
                                <h4 class="text-lg font-extrabold text-navy">${escapeHtml(cheque.cheque_no)}</h4>
                                <p class="mt-1 text-sm text-slate-500">${escapeHtml(cheque.party_label)}: <span class="font-semibold text-slate-700">${escapeHtml(cheque.party_name)}</span></p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold ring-1 ${escapeHtml(cheque.status_class)}">${escapeHtml(cheque.status)}</span>
                        </div>
                        <div class="mt-3 grid gap-2 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Bank</span><strong class="text-right text-navy">${escapeHtml(cheque.bank_name)}</strong></div>
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Amount</span><strong class="text-primary">${escapeHtml(cheque.amount)}</strong></div>
                        </div>
                        <div class="mt-3">
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 ${escapeHtml(cheque.type_class)}">${escapeHtml(cheque.type)}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="/cheques/\${cheque.id}" class="rounded-xl bg-primary px-3 py-2 text-center text-xs font-bold text-white">View</a>
                            <a href="#" class="rounded-xl bg-slate-100 px-3 py-2 text-center text-xs font-bold text-slate-700">Edit</a>
                            <a href="#" class="rounded-xl bg-green-50 px-3 py-2 text-center text-xs font-bold text-success">Mark Passed</a>
                            <a href="#" class="rounded-xl bg-red-50 px-3 py-2 text-center text-xs font-bold text-danger">Mark Returned</a>
                        </div>
                    </div>
                `).join('');
            }

            calendarModal.classList.remove('hidden');
            calendarModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeCalendarModal() {
            calendarModal.classList.add('hidden');
            calendarModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-calendar-date]').forEach((button) => {
            button.addEventListener('click', () => openCalendarModal(button.dataset.calendarDate, button.dataset.calendarLabel));
        });

        calendarModalClose?.addEventListener('click', closeCalendarModal);
        calendarModal?.addEventListener('click', (event) => {
            if (event.target === calendarModal) {
                closeCalendarModal();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCalendarModal();
            }
        });
    </script>
@endpush
