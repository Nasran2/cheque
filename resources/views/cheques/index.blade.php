@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $themeClasses = [
        'icon' => 'bg-blue-100 text-primary',
        'text' => 'text-primary',
        'soft' => 'bg-blue-50 text-primary',
    ];

    $summaryCards = [
        ['label' => 'Total Cheques', 'value' => number_format($summary['total_count']), 'sub' => 'Total records', 'icon' => 'fa-solid fa-money-check-dollar'],
        ['label' => 'Total Amount', 'value' => Currency::formatLkr($summary['total_amount']), 'sub' => 'Total amount', 'icon' => 'fa-solid fa-sack-dollar'],
        ['label' => 'Customer Received', 'value' => Currency::formatLkr($summary['customer_amount']), 'sub' => 'From customers', 'icon' => 'fa-solid fa-arrow-down'],
        ['label' => 'Own Issued', 'value' => Currency::formatLkr($summary['own_amount']), 'sub' => 'To suppliers', 'icon' => 'fa-solid fa-arrow-up'],
    ];

    $actionableStatuses = [
        Cheque::STATUS_PENDING,
        Cheque::STATUS_DEPOSITED,
        Cheque::STATUS_HOLD,
    ];
@endphp

@section('title', 'Cheques - Cheque Management System')
@section('page_title', 'Cheque Management')
@section('mobile_title', 'Cheques')

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

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-extrabold text-navy">All Cheques</h3>
            <p class="text-sm text-slate-500">View and manage all registered cheques in the system.</p>
        </div>
        <a href="{{ route('cheques.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
            <i class="fa-solid fa-plus"></i>
            Add New Cheque
        </a>
    </div>

    <div class="space-y-6">
        @if (request('supplier_id') || request('customer_id'))
            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-primary">
                Showing {{ number_format($summary['total_count']) }} cheque{{ $summary['total_count'] == 1 ? '' : 's' }}
                @if (request('supplier_id'))
                    for selected supplier.
                @elseif (request('customer_id'))
                    for selected customer.
                @endif
                <a href="{{ route('cheques.index') }}" class="ml-2 underline">Clear filter</a>
            </div>
        @endif

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <div class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl {{ $themeClasses['icon'] }}">
                        <i class="{{ $card['icon'] }} text-lg"></i>
                    </div>
                    <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                    <h4 class="mt-1 break-words text-xl font-extrabold text-navy sm:text-2xl">{{ $card['value'] }}</h4>
                    <p class="mt-2 text-xs font-bold {{ $themeClasses['text'] }}">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('cheques.index') }}" class="grid gap-3 rounded-3xl bg-white p-4 shadow-soft md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-[1.2fr_160px_160px_160px_150px_150px_auto_auto]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search cheque no, customer, supplier, bank..." class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">

            <select name="cheque_type" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                <option value="">All Types</option>
                <option value="{{ Cheque::TYPE_CUSTOMER_RECEIVED }}" @selected(request('cheque_type') === Cheque::TYPE_CUSTOMER_RECEIVED)>Customer Received</option>
                <option value="{{ Cheque::TYPE_OWN_ISSUED }}" @selected(request('cheque_type') === Cheque::TYPE_OWN_ISSUED)>Own Issued</option>
            </select>

            <select name="bank" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                <option value="">All Banks</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank }}" @selected(request('bank') === $bank)>{{ $bank }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                <option value="">All Statuses</option>
                <option value="{{ Cheque::STATUS_PENDING }}" @selected(request('status') === Cheque::STATUS_PENDING)>Pending</option>
                <option value="{{ Cheque::STATUS_DEPOSITED }}" @selected(request('status') === Cheque::STATUS_DEPOSITED)>Deposited</option>
                <option value="{{ Cheque::STATUS_PASSED }}" @selected(request('status') === Cheque::STATUS_PASSED)>Passed</option>
                <option value="{{ Cheque::STATUS_RETURNED }}" @selected(request('status') === Cheque::STATUS_RETURNED)>Returned</option>
                <option value="{{ Cheque::STATUS_HOLD }}" @selected(request('status') === Cheque::STATUS_HOLD)>Hold</option>
            </select>

            <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            @if (request('supplier_id'))
                <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">
            @endif
            @if (request('customer_id'))
                <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
            @endif

            <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">Search</button>
            <a href="{{ route('cheques.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-700 transition hover:bg-slate-200">Reset</a>
        </form>

        {{-- Mobile List View --}}
        <div class="mt-6 space-y-3 lg:hidden">
            @forelse ($cheques as $cheque)
                <div class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Cheque No</p>
                            <h4 class="text-lg font-extrabold text-navy">{{ $cheque->cheque_no }}</h4>
                            <p class="mt-1 text-sm text-slate-500">
                                @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                    <span class="block">From Customer: <strong class="text-slate-700">{{ $cheque->customer?->name ?? '—' }}</strong></span>
                                    <span class="block">To Supplier: <strong class="text-slate-700">{{ $cheque->supplier?->name ?? '—' }}</strong></span>
                                @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                    <span class="block">Customer: <strong class="text-slate-700">{{ $cheque->customer?->name ?? '—' }}</strong></span>
                                    <span class="block text-xs text-amber-600 font-semibold">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                @elseif ($cheque->isOwnIssued())
                                    <span class="block">Supplier: <strong class="text-slate-700">{{ $cheque->supplier?->name ?? '—' }}</strong></span>
                                    @if ($cheque->supplier_cheque_mode === 'received_customer_cheque')
                                        <span class="block text-xs text-amber-600 font-semibold">Original Customer: {{ $cheque->originalCustomer?->name ?? $cheque->customer?->name ?? '—' }}</span>
                                    @endif
                                @else
                                    Customer:
                                    <span class="font-semibold text-slate-700">{{ $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party' }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                            {{ ChequePresentation::statusLabel($cheque->status) }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Bank</span>
                            <strong class="text-right text-navy">{{ $cheque->bank_name }}</strong>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Date</span>
                            <strong class="text-navy">{{ $cheque->cheque_date?->format('d M Y') }}</strong>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Amount</span>
                            <strong class="text-primary">{{ Currency::formatLkr($cheque->amount) }}</strong>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::displayTypeBadgeClass($cheque) }}">
                            {{ ChequePresentation::displayTypeLabel($cheque) }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <a href="{{ route('cheques.show', $cheque->id) }}" class="rounded-xl bg-primary text-white px-3 py-2 text-center text-xs font-bold">View</a>
                        <a href="#" class="rounded-xl bg-slate-100 text-slate-700 px-3 py-2 text-center text-xs font-bold">Edit</a>
                        @if (in_array($cheque->status, $actionableStatuses, true))
                            <button type="button" onclick="openPassModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}', '{{ $cheque->cheque_date?->format('Y-m-d') }}')" class="w-full rounded-xl bg-emerald-50 px-3 py-2 text-center text-xs font-bold text-emerald-700">Pass</button>
                            <button type="button" onclick="openReturnModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}')" class="rounded-xl bg-red-50 px-3 py-2 text-center text-xs font-bold text-red-600">Return</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-3xl bg-white p-6 text-center text-sm text-slate-500 shadow-soft">No cheques found.</p>
            @endforelse
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden overflow-x-auto rounded-3xl bg-white shadow-soft lg:block">
            <table class="w-full text-left text-sm min-w-[980px]">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Cheque No</th>
                        <th>Type</th>
                        <th>Customer / Supplier</th>
                        <th>Bank</th>
                        <th>Cheque Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="pr-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cheques as $cheque)
                        <tr>
                            <td class="px-5 py-4 font-bold text-navy">{{ $cheque->cheque_no }}</td>
                            <td>
                                <span class="inline-flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier) ? 'bg-amber-100 text-amber-700' : ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED ? 'bg-teal/10 text-teal' : 'bg-primary/10 text-primary') }}">
                                        <i class="fa-solid {{ ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier) ? 'fa-share' : ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED ? 'fa-arrow-down' : 'fa-arrow-up') }} text-xs"></i>
                                    </span>
                                    {{ ChequePresentation::displayTypeLabel($cheque) }}
                                </span>
                            </td>
                            <td>
                                @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                    <span class="block font-bold">From Customer: {{ $cheque->customer?->name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-400">To Supplier: {{ $cheque->supplier?->name ?? '—' }}</span>
                                @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                    <span class="block font-bold">{{ $cheque->customer?->name ?? '—' }}</span>
                                    <span class="block text-xs text-amber-600 font-semibold">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                @elseif ($cheque->isOwnIssued())
                                    <span class="block font-bold">{{ $cheque->supplier?->name ?? '—' }}</span>
                                    @if ($cheque->supplier_cheque_mode === 'received_customer_cheque')
                                        <span class="block text-xs text-amber-600 font-semibold">Original Customer: {{ $cheque->originalCustomer?->name ?? $cheque->customer?->name ?? '—' }}</span>
                                    @endif
                                @else
                                    {{ $cheque->customer?->name ?? 'No party' }}
                                @endif
                            </td>
                            <td>{{ $cheque->bank_name }}</td>
                            <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                            <td class="font-bold text-primary">{{ Currency::formatLkr($cheque->amount) }}</td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                    {{ ChequePresentation::statusLabel($cheque->status) }}
                                </span>
                            </td>
                            <td class="pr-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('cheques.show', $cheque->id) }}" class="rounded-xl bg-primary text-white px-3 py-2 text-xs font-bold">View</a>
                                    <a href="#" class="rounded-xl bg-slate-100 text-slate-700 px-3 py-2 text-xs font-bold">Edit</a>
                                    @if (in_array($cheque->status, $actionableStatuses, true))
                                        <button type="button" onclick="openPassModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}', '{{ $cheque->cheque_date?->format('Y-m-d') }}')" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-500 hover:text-white">Pass</button>
                                        <button type="button" onclick="openReturnModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}')" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-500 hover:text-white">Return</button>
                                    @endif
                                    <button type="button"
                                        onclick="openSmsModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}', '{{ addslashes($cheque->customer?->phone ?? $cheque->supplier?->phone ?? '') }}')"
                                        class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-500 hover:text-white transition"
                                        title="Send SMS">
                                        <i class="fa-solid fa-comment-sms"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-500">No cheques found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $cheques->links() }}
        </div>
    </div>

    {{-- SMS Send Modal --}}
    <div id="returnModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 lg:p-8">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closeReturnModal()"></div>
        <form id="returnChequeForm" method="POST" class="relative z-10 w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            @csrf
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-extrabold text-navy">Mark Cheque Returned</h3>
                    <p class="text-xs text-slate-500">Cheque: <strong id="returnModalChequeNo">—</strong></p>
                </div>
                <button type="button" onclick="closeReturnModal()" class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Returned Date</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Returned Reason</label>
                    <input type="text" name="returned_reason" placeholder="Insufficient funds, signature mismatch..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Return Charge (Rs)</label>
                    <input type="number" step="0.01" min="0" name="return_charge" value="0" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-slate-100 px-6 py-5">
                <button type="button" onclick="closeReturnModal()" class="flex-1 rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">Cancel</button>
                <button class="flex-1 rounded-2xl bg-red-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-red-500/20">Mark Returned</button>
            </div>
        </form>
    </div>

    {{-- Pass Cheque Modal --}}
    <div id="passModal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4 lg:p-8">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closePassModal()"></div>
        <form id="passChequeForm" method="POST" action="" class="relative z-10 w-full max-w-sm rounded-3xl bg-white shadow-2xl">
            @csrf
            <div class="p-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                    <i class="fa-solid fa-check-double text-2xl"></i>
                </div>
                <h3 class="mb-2 text-xl font-extrabold text-navy">Pass Cheque <span id="passModalChequeNo"></span></h3>
                <p id="passModalMessage" class="text-sm font-medium text-slate-500">Are you sure you want to mark this cheque as passed?</p>
            </div>
            
            <div class="flex items-center gap-3 border-t border-slate-100 px-6 py-5">
                <button type="button" onclick="closePassModal()" class="flex-1 rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">Cancel</button>
                <button class="flex-1 rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20">Mark Passed</button>
            </div>
        </form>
    </div>

    {{-- SMS Send Modal --}}
    <div id="smsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 lg:p-8">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closeSmsModal()"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-extrabold text-navy">Send SMS</h3>
                    <p class="text-xs text-slate-500">Cheque: <strong id="smsModalChequeNo">—</strong></p>
                </div>
                <button type="button" onclick="closeSmsModal()" class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Quick Template</label>
                    <select id="smsTemplateSelect" onchange="applyTemplate()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="">— Custom Message —</option>
                        <option value="customer_cheque_reminder">Customer Cheque Reminder</option>
                        <option value="supplier_cheque_reminder">Supplier Cheque Reminder</option>
                        <option value="customer_cheque_returned">Customer Cheque Returned</option>
                        <option value="supplier_cheque_returned">Own Cheque Returned</option>
                        <option value="customer_cheque_passed">Customer Cheque Passed</option>
                        <option value="supplier_cheque_passed">Own Cheque Passed</option>
                        <option value="customer_cheque_overdue">Overdue Customer Cheque</option>
                        <option value="supplier_cheque_overdue">Overdue Supplier Cheque</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Recipient Phone</label>
                    <input type="text" id="smsPhone" placeholder="e.g. 0771234567"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-navy">Message</label>
                    <textarea id="smsMessage" rows="4" placeholder="Type your message here..."
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm leading-relaxed outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>
                </div>

                <div id="smsModalResult" class="hidden rounded-2xl px-4 py-3 text-sm font-semibold"></div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button" id="smsSendBtn" onclick="submitSms()"
                    class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-primary py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                    <i class="fa-solid fa-paper-plane"></i>Send SMS
                </button>
                <button type="button" onclick="closeSmsModal()"
                    class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-200">Cancel</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // SMS modal scripts
        let currentChequeId = null;

        function openPassModal(chequeId, chequeNo, chequeDate) {
            const form = document.getElementById('passChequeForm');
            form.action = `/cheques/${chequeId}/mark-passed`;
            document.getElementById('passModalChequeNo').textContent = chequeNo;
            
            const msgEl = document.getElementById('passModalMessage');
            const today = new Date().toISOString().split('T')[0];
            
            if (chequeDate && chequeDate > today) {
                msgEl.innerHTML = `The cheque pass date is <strong>${chequeDate}</strong>. Do you really want to pass it now?`;
                msgEl.classList.remove('text-slate-500');
                msgEl.classList.add('text-amber-600');
            } else {
                msgEl.textContent = 'Are you sure you want to mark this cheque as passed?';
                msgEl.classList.remove('text-amber-600');
                msgEl.classList.add('text-slate-500');
            }
            
            document.getElementById('passModal').classList.remove('hidden');
            document.getElementById('passModal').classList.add('flex');
        }

        function closePassModal() {
            document.getElementById('passModal').classList.add('hidden');
            document.getElementById('passModal').classList.remove('flex');
        }

        function openReturnModal(chequeId, chequeNo) {
            const form = document.getElementById('returnChequeForm');
            form.action = `/cheques/${chequeId}/mark-returned`;
            document.getElementById('returnModalChequeNo').textContent = chequeNo;
            document.getElementById('returnModal').classList.remove('hidden');
            document.getElementById('returnModal').classList.add('flex');
        }

        function closeReturnModal() {
            document.getElementById('returnModal').classList.add('hidden');
            document.getElementById('returnModal').classList.remove('flex');
            document.getElementById('returnChequeForm').reset();
        }

        function openSmsModal(chequeId, chequeNo, defaultPhone) {
            currentChequeId = chequeId;
            document.getElementById('smsModalChequeNo').textContent = chequeNo;
            document.getElementById('smsPhone').value = defaultPhone ?? '';
            document.getElementById('smsMessage').value = '';
            document.getElementById('smsTemplateSelect').value = '';
            document.getElementById('smsModalResult').className = 'hidden';
            document.getElementById('smsModal').classList.remove('hidden');
            document.getElementById('smsModal').classList.add('flex');
        }

        function closeSmsModal() {
            document.getElementById('smsModal').classList.add('hidden');
            document.getElementById('smsModal').classList.remove('flex');
            currentChequeId = null;
        }

        function applyTemplate() {
            const key = document.getElementById('smsTemplateSelect').value;
            if (!key) return;
            document.getElementById('smsMessage').value = '[Template will be auto-filled with cheque data when sent]';
        }

        async function submitSms() {
            if (!currentChequeId) return;

            const btn     = document.getElementById('smsSendBtn');
            const phone   = document.getElementById('smsPhone').value.trim();
            const message = document.getElementById('smsMessage').value.trim();
            const tplKey  = document.getElementById('smsTemplateSelect').value;

            if (!phone) { showSmsResult(false, 'Please enter a phone number.'); return; }
            if (!message && !tplKey) { showSmsResult(false, 'Please enter a message or select a template.'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Sending…';

            try {
                const res = await fetch(`/cheques/${currentChequeId}/send-sms`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone,
                        message: tplKey ? 'Using template' : message,
                        template_key: tplKey || null,
                    }),
                });

                const data = await res.json();
                showSmsResult(data.success, data.message);

                if (data.success) {
                    setTimeout(closeSmsModal, 2000);
                }
            } catch (e) {
                showSmsResult(false, 'Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i>Send SMS';
            }
        }

        function showSmsResult(success, message) {
            const el = document.getElementById('smsModalResult');
            el.className = `flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold ${
                success ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                        : 'bg-red-50 text-red-700 border border-red-100'
            }`;
            el.innerHTML = `<i class="fa-solid ${success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${message}`;
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeSmsModal();
                closeReturnModal();
            }
        });
    </script>
@endpush
