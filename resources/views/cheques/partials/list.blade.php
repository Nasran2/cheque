@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $themeClasses = match ($theme) {
        'green' => [
            'icon' => 'bg-green-100 text-success',
            'text' => 'text-success',
            'soft' => 'bg-green-50 text-success',
        ],
        'red' => [
            'icon' => 'bg-red-100 text-danger',
            'text' => 'text-danger',
            'soft' => 'bg-red-50 text-danger',
        ],
        default => [
            'icon' => 'bg-orange-100 text-warning',
            'text' => 'text-warning',
            'soft' => 'bg-orange-50 text-warning',
        ],
    };

    $summaryCards = [
        ['label' => "{$statusLabel} Cheques", 'value' => number_format($summary['total_count']), 'sub' => 'Total records', 'icon' => $icon],
        ['label' => "{$statusLabel} Amount", 'value' => Currency::formatLkr($summary['total_amount']), 'sub' => 'Total amount', 'icon' => 'fa-solid fa-sack-dollar'],
        ['label' => "Customer {$statusLabel}", 'value' => Currency::formatLkr($summary['customer_amount']), 'sub' => 'Customer received', 'icon' => 'fa-solid fa-arrow-down'],
        ['label' => "Own {$statusLabel}", 'value' => Currency::formatLkr($summary['own_amount']), 'sub' => 'Own issued', 'icon' => 'fa-solid fa-arrow-up'],
    ];
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h3 class="text-xl font-extrabold text-navy">{{ $pageTitle }}</h3>
        <p class="text-sm text-slate-500">{{ $pageDescription }}</p>
    </div>
    <a href="{{ route('cheques.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20">
        <i class="fa-solid fa-plus"></i>
        Add New Cheque
    </a>
</div>

<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ($summaryCards as $card)
        <div class="rounded-3xl bg-white p-5 shadow-soft">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl {{ $themeClasses['icon'] }}">
                <i class="{{ $card['icon'] }}"></i>
            </div>
            <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
            <h4 class="mt-1 break-words text-2xl font-extrabold text-navy">{{ $card['value'] }}</h4>
            <p class="mt-2 text-xs font-bold {{ $themeClasses['text'] }}">{{ $card['sub'] }}</p>
        </div>
    @endforeach
</div>

<form method="GET" action="{{ route($routeName) }}" class="mt-6 grid gap-3 rounded-3xl bg-white p-4 shadow-soft md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-[1.3fr_190px_190px_170px_170px_auto_auto]">
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

    <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">

    <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white">Search</button>
    <a href="{{ route($routeName) }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-700">Reset</a>
</form>

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
                        @else
                            {{ $cheque->isCustomerReceived() ? 'Customer' : 'Supplier' }}:
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
                    <strong class="{{ $themeClasses['text'] }}">{{ Currency::formatLkr($cheque->amount) }}</strong>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::displayTypeBadgeClass($cheque) }}">
                    {{ ChequePresentation::displayTypeLabel($cheque) }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                @foreach ($actions as $action)
                    @if ($action === 'View')
                        <a href="{{ route('cheques.show', $cheque->id) }}" class="rounded-xl bg-primary text-white px-3 py-2 text-center text-xs font-bold">View</a>
                    @else
                        <a href="#" class="rounded-xl bg-slate-100 text-slate-700 px-3 py-2 text-center text-xs font-bold">{{ $action }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    @empty
        <p class="rounded-3xl bg-white p-6 text-center text-sm text-slate-500 shadow-soft">{{ $emptyText }}</p>
    @endforelse
</div>

<div class="mt-6 hidden overflow-x-auto rounded-3xl bg-white shadow-soft lg:block">
    <table class="min-w-[980px] w-full text-left text-sm">
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
                        @else
                            {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party' }}
                        @endif
                    </td>
                    <td>{{ $cheque->bank_name }}</td>
                    <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                    <td class="font-bold {{ $themeClasses['text'] }}">{{ Currency::formatLkr($cheque->amount) }}</td>
                    <td>
                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                            {{ ChequePresentation::statusLabel($cheque->status) }}
                        </span>
                    </td>
                    <td class="pr-5">
                        <div class="flex justify-end gap-2">
                            @foreach ($actions as $action)
                                @if ($action === 'View')
                                    <a href="{{ route('cheques.show', $cheque->id) }}" class="rounded-xl bg-primary text-white px-3 py-2 text-xs font-bold">View</a>
                                @else
                                    <a href="#" class="rounded-xl bg-slate-100 text-slate-700 px-3 py-2 text-xs font-bold">{{ $action }}</a>
                                @endif
                            @endforeach
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
                    <td colspan="8" class="px-5 py-8 text-center text-slate-500">{{ $emptyText }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $cheques->links() }}
</div>

{{-- ── SMS Send Modal ────────────────────────────────────────────────────── --}}
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
            {{-- Template selector --}}
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

            {{-- Phone --}}
            <div>
                <label class="mb-2 block text-sm font-bold text-navy">Recipient Phone</label>
                <input type="text" id="smsPhone" placeholder="e.g. 0771234567"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            </div>

            {{-- Message --}}
            <div>
                <label class="mb-2 block text-sm font-bold text-navy">Message</label>
                <textarea id="smsMessage" rows="4" placeholder="Type your message here..."
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm leading-relaxed outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>
            </div>

            {{-- Result --}}
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

@push('scripts')
<script>
    let currentChequeId = null;

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
        // Notify: actual variable substitution happens server-side
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

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSmsModal(); });
</script>
@endpush
