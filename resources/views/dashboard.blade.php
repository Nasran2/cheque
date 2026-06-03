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

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 2xl:grid-cols-6">
        <x-dashboard-card icon="fa-regular fa-rectangle-list" title="Total Cheques" :count="$summary['total_count']" :amount="$summary['total_amount']" color="primary" sub="Total Value" />
        <x-dashboard-card icon="fa-regular fa-clock" title="Pending" :count="$summary['pending_count']" :amount="$summary['pending_amount']" color="warning" sub="Amount" />
        <x-dashboard-card icon="fa-regular fa-circle-check" title="Passed" :count="$summary['passed_count']" :amount="$summary['passed_amount']" color="success" sub="Amount" />
        <x-dashboard-card icon="fa-solid fa-rotate" title="Returned" :count="$summary['returned_count']" :amount="$summary['returned_amount']" color="danger" sub="Amount" />
        <x-dashboard-card icon="fa-solid fa-arrow-down" title="Amount to Receive" :count="null" :amount="$summary['amount_to_receive']" color="teal" sub="From Customers" />
        <x-dashboard-card icon="fa-solid fa-arrow-up" title="Amount to Pay" :count="null" :amount="$summary['amount_to_pay']" color="purplePay" sub="To Suppliers" />
    </div>

    <div class="mt-6 grid gap-6 2xl:grid-cols-[1fr_560px]">
        <section id="recent-cheques" class="rounded-3xl bg-white p-5 shadow-soft">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">Recent Cheques</h3>
                <a href="#recent-cheques" class="text-sm font-bold text-primary">View All</a>
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

        <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-1">
            <section id="upcoming-cheques" class="rounded-3xl bg-white p-5 shadow-soft">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-extrabold text-navy">Upcoming Cheques</h3>
                    <a href="#upcoming-cheques" class="text-sm font-bold text-primary">View All</a>
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

            <section id="overdue-cheques" class="rounded-3xl bg-white p-5 shadow-soft">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-extrabold text-navy">Overdue Alerts</h3>
                    <a href="#overdue-cheques" class="text-sm font-bold text-primary">View All</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($overdueCheques as $cheque)
                        <div class="grid grid-cols-[34px_1fr_auto] gap-3 py-3 text-sm">
                            <i class="fa-solid fa-triangle-exclamation pt-1 text-danger"></i>
                            <div>
                                <p class="font-bold text-navy">{{ $cheque->cheque_no }}</p>
                                <p class="text-xs text-slate-500">{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? $cheque->bank_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-500">{{ $cheque->cheque_date?->format('d M Y') }}</p>
                                <p class="font-bold text-danger">{{ Currency::formatLkr($cheque->amount) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No overdue cheques.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr_1.2fr_1.45fr] 2xl:grid-cols-[1fr_1fr_1.2fr_1.45fr_380px]">
        <section class="rounded-3xl bg-white p-5 shadow-soft">
            <h3 class="text-lg font-extrabold text-navy">Customer Due Summary</h3>
            <div class="mt-5 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-white">
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

        <section class="rounded-3xl bg-white p-5 shadow-soft">
            <h3 class="text-lg font-extrabold text-navy">Supplier Payable Summary</h3>
            <div class="mt-5 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-purplePay text-white">
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

        <section id="status-chart" class="rounded-3xl bg-white p-5 shadow-soft">
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

        <section id="monthly-chart" class="rounded-3xl bg-white p-5 shadow-soft">
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

        <section id="add-cheque" class="rounded-3xl bg-white p-5 shadow-soft xl:col-span-4 2xl:col-span-1 2xl:row-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold text-navy">Add Cheque</h3>
                <i class="fa-solid fa-up-right-from-square text-slate-400"></i>
            </div>

            <form method="POST" action="{{ route('cheques.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-xs font-bold text-navy">Cheque Type <span class="text-danger">*</span></label>
                    <select name="cheque_type" id="chequeType" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                        <option value="{{ Cheque::TYPE_CUSTOMER_RECEIVED }}">Received (From Customer)</option>
                        <option value="{{ Cheque::TYPE_OWN_ISSUED }}">Issued (To Supplier)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold text-navy">Cheque Number <span class="text-danger">*</span></label>
                    <input name="cheque_no" value="{{ old('cheque_no') }}" placeholder="Enter cheque number" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-1">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-navy">Bank <span class="text-danger">*</span></label>
                        <input name="bank_name" value="{{ old('bank_name') }}" placeholder="Enter bank" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-1">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-navy">Date <span class="text-danger">*</span></label>
                        <input type="date" name="cheque_date" value="{{ old('cheque_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold text-navy">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" placeholder="Enter amount" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                    </div>
                </div>

                <input type="hidden" name="supplier_cheque_mode" value="own_cheque">

                <div id="dashboardCustomerContainer">
                    <label class="mb-2 block text-xs font-bold text-navy">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="dashboardCustomerSelect" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="">Select customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                    </select>
                </div>

                <div id="dashboardSupplierContainer" class="hidden">
                    <label class="mb-2 block text-xs font-bold text-navy">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="dashboardSupplierSelect" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="">Select supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold text-navy">Status <span class="text-danger">*</span></label>
                    <select name="status" id="dashboardStatusSelect" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                        <option value="{{ Cheque::STATUS_PENDING }}">Pending</option>
                        <option value="{{ Cheque::STATUS_DEPOSITED }}" id="dashboardDepositedOption">Deposited</option>
                        <option value="{{ Cheque::STATUS_PASSED }}">Passed</option>
                        <option value="{{ Cheque::STATUS_RETURNED }}">Returned</option>
                        <option value="{{ Cheque::STATUS_CANCELLED }}">Cancelled</option>
                        <option value="{{ Cheque::STATUS_HOLD }}">Hold</option>
                    </select>
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                    <i class="fa-regular fa-calendar-check"></i>
                    Save Cheque
                </button>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const chequeType = document.getElementById('chequeType');
        const dashboardCustomerContainer = document.getElementById('dashboardCustomerContainer');
        const dashboardSupplierContainer = document.getElementById('dashboardSupplierContainer');
        const dashboardCustomerSelect = document.getElementById('dashboardCustomerSelect');
        const dashboardSupplierSelect = document.getElementById('dashboardSupplierSelect');
        const dashboardStatusSelect = document.getElementById('dashboardStatusSelect');
        const dashboardDepositedOption = document.getElementById('dashboardDepositedOption');

        function syncPartyOptions() {
            if (!chequeType || !dashboardCustomerContainer || !dashboardSupplierContainer) return;

            const isCustomerReceived = chequeType.value === '{{ Cheque::TYPE_CUSTOMER_RECEIVED }}';

            dashboardCustomerContainer.classList.toggle('hidden', !isCustomerReceived);
            dashboardSupplierContainer.classList.toggle('hidden', isCustomerReceived);
            dashboardCustomerSelect.required = isCustomerReceived;
            dashboardSupplierSelect.required = !isCustomerReceived;

            dashboardDepositedOption.hidden = !isCustomerReceived;
            dashboardDepositedOption.disabled = !isCustomerReceived;
            if (!isCustomerReceived && dashboardStatusSelect.value === '{{ Cheque::STATUS_DEPOSITED }}') {
                dashboardStatusSelect.value = '{{ Cheque::STATUS_PENDING }}';
            }
        }

        chequeType?.addEventListener('change', syncPartyOptions);
        syncPartyOptions();
    </script>
@endpush
