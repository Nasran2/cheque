@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $statusMeta = [
        Cheque::STATUS_PENDING => ['label' => 'Pending', 'color' => '#F97316'],
        Cheque::STATUS_PASSED => ['label' => 'Passed', 'color' => '#16A34A'],
        Cheque::STATUS_RETURNED => ['label' => 'Returned', 'color' => '#EF4444'],
        Cheque::STATUS_HOLD => ['label' => 'Hold', 'color' => '#64748B'],
        Cheque::STATUS_DEPOSITED => ['label' => 'Deposited', 'color' => '#0B5CFF'],
    ];
    $totalStatusCount = max(1, $statusCounts->sum());
    $maxMonthlyAmount = max(1, $months->max('amount') ?: 1);
@endphp

@section('title', 'Dashboard - Cheque Management System')
@section('page_title', 'Cheque Management Dashboard')
@section('mobile_title', 'Cheque Dashboard')
@section('alert_count', $overdueCheques->count())

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <x-dashboard-card icon="fa-regular fa-rectangle-list" title="Total Cheques" :count="$summary['total_count']" :amount="$summary['total_amount']" color="primary" sub="Total Value" />
        <x-dashboard-card icon="fa-regular fa-clock" title="Pending" :count="$summary['pending_count']" :amount="$summary['pending_amount']" color="warning" sub="Amount" />
        <x-dashboard-card icon="fa-regular fa-circle-check" title="Passed" :count="$summary['passed_count']" :amount="$summary['passed_amount']" color="success" sub="Amount" />
        <x-dashboard-card icon="fa-solid fa-rotate" title="Returned" :count="$summary['returned_count']" :amount="$summary['returned_amount']" color="danger" sub="Amount" />
        <x-dashboard-card icon="fa-solid fa-arrow-down" title="Amount to Receive" :count="null" :amount="$summary['amount_to_receive']" color="teal" sub="From Customers" />
        <x-dashboard-card icon="fa-solid fa-arrow-up" title="Amount to Pay" :count="null" :amount="$summary['amount_to_pay']" color="purplePay" sub="To Suppliers" />
    </div>

    {{-- Cheque Reminders Section --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Customer Reminders Card --}}
        <section class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">
                    <i class="fa-solid fa-users text-teal mr-2"></i>Customer Cheque Reminders
                </h3>
                @if ($customerReminders->isNotEmpty())
                    <span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-bold text-teal">
                        {{ $customerReminders->count() }} Alert{{ $customerReminders->count() > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>

            @if ($customerReminders->isEmpty())
                <div class="rounded-2xl border border-slate-100/60 bg-slate-50/50 p-6 text-center text-sm text-slate-500">
                    <i class="fa-regular fa-bell-slash text-slate-300 text-xl block mb-2"></i>
                    No customer cheques due or overdue.
                </div>
            @else
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    @foreach ($customerReminders as $cheque)
                        @php
                            $daysDiff = today()->diffInDays($cheque->cheque_date, false);
                            $countdownText = $daysDiff < 0 ? 'Overdue by ' . abs($daysDiff) . ' days' : ($daysDiff === 0 ? 'Due today' : ($daysDiff === 1 ? 'Due tomorrow' : 'Due in ' . $daysDiff . ' days'));
                            $countdownClass = $daysDiff < 0 ? 'bg-red-50 text-red-700 border-red-100' : ($daysDiff === 0 ? 'bg-orange-50 text-orange-700 border-orange-100' : 'bg-slate-50 text-slate-700 border-slate-100');
                        @endphp
                        <div class="rounded-2xl border border-slate-100 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('cheques.show', $cheque->id) }}" class="font-extrabold text-navy hover:underline">#{{ $cheque->cheque_no }}</a>
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold border {{ $countdownClass }}">
                                        {{ $countdownText }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 font-medium">
                                    Customer: <strong class="text-slate-700">{{ $cheque->customer?->name ?? 'Unknown Customer' }}</strong>
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ $cheque->bank_name }} | Due Date: {{ $cheque->cheque_date?->format('d M Y') }}
                                </p>
                            </div>
                            <div class="text-left sm:text-right shrink-0">
                                <p class="text-base font-black text-primary">{{ Currency::formatLkr($cheque->amount) }}</p>
                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400 mt-0.5">
                                    {{ ChequePresentation::statusLabel($cheque->status) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Supplier Reminders Card --}}
        <section class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">
                    <i class="fa-solid fa-truck-field text-purplePay mr-2"></i>Supplier Cheque Reminders
                </h3>
                @if ($supplierReminders->isNotEmpty())
                    <span class="rounded-full bg-purplePay/10 px-3 py-1 text-xs font-bold text-purplePay">
                        {{ $supplierReminders->count() }} Alert{{ $supplierReminders->count() > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>

            @if ($supplierReminders->isEmpty())
                <div class="rounded-2xl border border-slate-100/60 bg-slate-50/50 p-6 text-center text-sm text-slate-500">
                    <i class="fa-regular fa-bell-slash text-slate-300 text-xl block mb-2"></i>
                    No supplier cheques due or overdue.
                </div>
            @else
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    @foreach ($supplierReminders as $cheque)
                        @php
                            $daysDiff = today()->diffInDays($cheque->cheque_date, false);
                            $countdownText = $daysDiff < 0 ? 'Overdue by ' . abs($daysDiff) . ' days' : ($daysDiff === 0 ? 'Due today' : ($daysDiff === 1 ? 'Due tomorrow' : 'Due in ' . $daysDiff . ' days'));
                            $countdownClass = $daysDiff < 0 ? 'bg-red-50 text-red-700 border-red-100' : ($daysDiff === 0 ? 'bg-orange-50 text-orange-700 border-orange-100' : 'bg-slate-50 text-slate-700 border-slate-100');
                            
                            $isTransferred = ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier);
                        @endphp
                        <div class="rounded-2xl border border-slate-100 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('cheques.show', $cheque->id) }}" class="font-extrabold text-navy hover:underline">#{{ $cheque->cheque_no }}</a>
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold border {{ $countdownClass }}">
                                        {{ $countdownText }}
                                    </span>
                                    <span class="rounded px-2 py-0.5 text-[10px] font-extrabold uppercase {{ $isTransferred ? 'bg-amber-100 text-amber-800' : 'bg-violet-100 text-violet-800' }}">
                                        {{ $isTransferred ? 'Transferred' : 'Own Issued' }}
                                    </span>
                                </div>
                                
                                <p class="text-sm text-slate-500 font-medium">
                                    @if ($isTransferred)
                                        To Supplier: <strong class="text-slate-700">{{ $cheque->givenToSupplier?->name ?? 'Unknown Supplier' }}</strong>
                                        <span class="text-xs text-slate-400 block sm:inline sm:ml-2">(From Customer: {{ $cheque->customer?->name ?? 'Unknown' }})</span>
                                    @else
                                        To Supplier: <strong class="text-slate-700">{{ $cheque->supplier?->name ?? 'Unknown Supplier' }}</strong>
                                    @endif
                                </p>
                                
                                <p class="text-xs text-slate-400">
                                    {{ $cheque->bank_name }} | Due Date: {{ $cheque->cheque_date?->format('d M Y') }}
                                </p>
                            </div>
                            <div class="text-left sm:text-right shrink-0">
                                <p class="text-base font-black text-purplePay">{{ Currency::formatLkr($cheque->amount) }}</p>
                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400 mt-0.5">
                                    {{ ChequePresentation::statusLabel($cheque->status) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section id="recent-cheques" class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">Recent Cheques</h3>
                <a href="{{ route('cheques.index') }}" class="text-sm font-bold text-primary">View All</a>
            </div>

            <div class="space-y-3 lg:hidden">
                @forelse ($recentCheques->take(6) as $cheque)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-bold text-navy">{{ $cheque->cheque_no }}</h4>
                                <p class="text-sm text-slate-500">{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party' }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                {{ ChequePresentation::statusLabel($cheque->status) }}
                            </span>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="text-slate-500">{{ $cheque->bank_name }}</span>
                            <span class="font-bold text-navy">{{ Currency::formatLkr($cheque->amount) }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ $cheque->cheque_date?->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No cheques found yet.</p>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-500">
                            <th class="py-4">Cheque No</th>
                            <th>Type</th>
                            <th>Customer / Supplier</th>
                            <th>Bank</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentCheques as $cheque)
                            <tr>
                                <td class="py-4 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                                <td>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $cheque->isCustomerReceived() ? 'bg-teal/10 text-teal' : 'bg-primary/10 text-primary' }}">
                                            <i class="fa-solid {{ $cheque->isCustomerReceived() ? 'fa-arrow-down' : 'fa-arrow-up' }} text-xs"></i>
                                        </span>
                                        {{ ChequePresentation::typeLabel($cheque->cheque_type) }}
                                    </span>
                                </td>
                                <td>{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party' }}</td>
                                <td>{{ $cheque->bank_name }}</td>
                                <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                                <td class="font-bold">{{ Currency::formatLkr($cheque->amount) }}</td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                        {{ ChequePresentation::statusLabel($cheque->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">No cheques found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="upcoming-cheques" class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">Upcoming Cheques</h3>
                <a href="{{ route('cheques.upcoming') }}" class="text-sm font-bold text-primary">View All</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($upcomingCheques as $cheque)
                    <div class="grid grid-cols-[34px_1fr_auto] gap-3 py-3 text-sm">
                        <i class="fa-regular fa-calendar-days pt-1 text-primary"></i>
                        <div>
                            <p class="font-bold text-navy">{{ $cheque->cheque_no }}</p>
                            <p class="text-xs text-slate-500">{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? $cheque->bank_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-slate-500">{{ $cheque->cheque_date?->format('d M Y') }}</p>
                            <p class="font-bold text-navy">{{ Currency::formatLkr($cheque->amount) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No upcoming cheques.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
        <section class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <h3 class="text-lg font-extrabold text-navy">Customer Due Summary</h3>
            <div class="mt-5 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Customers</p>
                    <p class="text-2xl font-extrabold text-navy">{{ $customerCount }}</p>
                </div>
            </div>
            <p class="mt-5 text-sm text-slate-500">Total Due Amount</p>
            <p class="text-2xl font-extrabold text-primary">{{ Currency::formatLkr($summary['amount_to_receive']) }}</p>
        </section>

        <section class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <h3 class="text-lg font-extrabold text-navy">Supplier Payable Summary</h3>
            <div class="mt-5 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-purplePay/10 text-purplePay">
                    <i class="fa-solid fa-cart-flatbed text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Suppliers</p>
                    <p class="text-2xl font-extrabold text-navy">{{ $supplierCount }}</p>
                </div>
            </div>
            <p class="mt-5 text-sm text-slate-500">Total Payable Amount</p>
            <p class="text-2xl font-extrabold text-purplePay">{{ Currency::formatLkr($summary['amount_to_pay']) }}</p>
        </section>

        <section id="status-chart" class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <h3 class="text-lg font-extrabold text-navy">Cheque Status Distribution</h3>
            <div class="mt-5 flex items-center gap-5">
                <div class="relative flex h-32 w-32 shrink-0 items-center justify-center rounded-full" style="background: conic-gradient(
                    #F97316 0 {{ ($statusCounts[Cheque::STATUS_PENDING] / $totalStatusCount) * 100 }}%,
                    #16A34A 0 {{ (($statusCounts[Cheque::STATUS_PENDING] + $statusCounts[Cheque::STATUS_PASSED]) / $totalStatusCount) * 100 }}%,
                    #EF4444 0 {{ (($statusCounts[Cheque::STATUS_PENDING] + $statusCounts[Cheque::STATUS_PASSED] + $statusCounts[Cheque::STATUS_RETURNED]) / $totalStatusCount) * 100 }}%,
                    #64748B 0 {{ (($statusCounts[Cheque::STATUS_PENDING] + $statusCounts[Cheque::STATUS_PASSED] + $statusCounts[Cheque::STATUS_RETURNED] + $statusCounts[Cheque::STATUS_HOLD]) / $totalStatusCount) * 100 }}%,
                    #0B5CFF 0 100%
                );">
                    <div class="flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white">
                        <span class="text-2xl font-extrabold text-navy">{{ $summary['total_count'] }}</span>
                        <span class="text-xs text-slate-500">Total</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    @foreach ($statusMeta as $status => $meta)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-600">
                                <span class="h-3 w-3 rounded-full" style="background: {{ $meta['color'] }}"></span>
                                {{ $meta['label'] }}
                            </span>
                            <strong class="text-navy">{{ $statusCounts[$status] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="monthly-chart" class="rounded-3xl bg-white p-6 md:p-8 shadow-soft border border-slate-100/40">
            <h3 class="text-lg font-extrabold text-navy">Monthly Cheque Totals</h3>
            <div class="mt-6 flex h-48 items-end gap-3 border-b border-slate-100">
                @foreach ($months as $month)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="text-[10px] font-bold text-primary">{{ number_format($month['amount'] / 1000000, 1) }}M</div>
                        <div class="w-full rounded-t-lg bg-primary" style="height: {{ max(10, ($month['amount'] / $maxMonthlyAmount) * 150) }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 grid grid-cols-6 gap-2 text-center text-[10px] text-slate-500">
                @foreach ($months as $month)
                    <span>{{ $month['label'] }}</span>
                @endforeach
            </div>
        </section>
    </div>
@endsection


