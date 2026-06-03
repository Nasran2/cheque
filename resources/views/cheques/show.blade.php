@extends('layouts.app')

@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    // Build merged timeline history
    $historyEvents = collect();

    // 1. Parent events
    foreach ($cheque->transactions as $tx) {
        $historyEvents->push([
            'date' => $tx->created_at,
            'action' => $tx->action,
            'old_status' => $tx->old_status,
            'new_status' => $tx->new_status,
            'amount' => $tx->amount,
            'note' => $tx->note,
            'user' => $tx->createdBy?->name ?? 'System',
            'type' => 'parent',
            'label' => 'Customer Cheque',
        ]);
    }

    // 2. Child events (transferred cheque)
    if ($transferredToCheque) {
        foreach ($transferredToCheque->transactions as $tx) {
            if ($tx->action === 'created') {
                $historyEvents->push([
                    'date' => $tx->created_at,
                    'action' => 'transferred',
                    'old_status' => null,
                    'new_status' => null,
                    'amount' => $tx->amount,
                    'note' => $tx->note,
                    'user' => $tx->createdBy?->name ?? 'System',
                    'type' => 'child_created',
                    'label' => 'Supplier Cheque',
                ]);
            } else {
                $historyEvents->push([
                    'date' => $tx->created_at,
                    'action' => $tx->action,
                    'old_status' => $tx->old_status,
                    'new_status' => $tx->new_status,
                    'amount' => $tx->amount,
                    'note' => $tx->note,
                    'user' => $tx->createdBy?->name ?? 'System',
                    'type' => 'child',
                    'label' => 'Supplier Cheque',
                ]);
            }
        }
    }

    // Sort chronologically by date (oldest first)
    $historyEvents = $historyEvents->sortBy('date');

    // Build merged audit logs
    $mergedAudits = collect();
    foreach ($cheque->auditLogs as $log) {
        $mergedAudits->push([
            'date' => $log->created_at,
            'action' => $log->action,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'ip' => $log->ip_address,
            'device' => $log->device,
            'note' => $log->note,
            'user' => $log->user?->name ?? 'System',
            'label' => 'Customer Cheque',
        ]);
    }
    if ($transferredToCheque) {
        foreach ($transferredToCheque->auditLogs as $log) {
            $mergedAudits->push([
                'date' => $log->created_at,
                'action' => $log->action,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip' => $log->ip_address,
                'device' => $log->device,
                'note' => $log->note,
                'user' => $log->user?->name ?? 'System',
                'label' => 'Supplier Cheque',
            ]);
        }
    }
    $mergedAudits = $mergedAudits->sortByDesc('date');
@endphp

@section('title', 'Cheque Details - Cheque Management System')
@section('page_title', 'Cheque Details')
@section('mobile_title', 'Cheque Details')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Back & Header Navigation --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('cheques.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-navy transition">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Cheques List
            </a>
            
            <div class="flex gap-2">
                <button type="button" 
                    onclick="openSmsModal({{ $cheque->id }}, '{{ addslashes($cheque->cheque_no) }}', '{{ addslashes($cheque->customer?->phone ?? $cheque->supplier?->phone ?? '') }}')"
                    class="flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-500 hover:text-white">
                    <i class="fa-solid fa-comment-sms"></i>Send SMS
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Main Two-Column Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Details & Transfer --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Main Cheque Card --}}
                <div class="overflow-hidden rounded-3xl bg-white shadow-soft">
                    {{-- Header Band --}}
                    <div class="flex flex-col gap-4 bg-slate-50 px-6 py-5 border-b border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <i class="fa-solid fa-money-check text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-navy">Cheque #{{ $cheque->cheque_no }}</h3>
                                <p class="text-xs text-slate-400">Recorded on {{ $cheque->created_at->format('d M Y - h:i A') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::displayTypeBadgeClass($cheque) }}">
                                {{ ChequePresentation::displayTypeLabel($cheque) }}
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ChequePresentation::statusBadgeClass($cheque->status) }}">
                                {{ ChequePresentation::statusLabel($cheque->status) }}
                            </span>
                        </div>
                    </div>

                    {{-- Detail Fields Grid --}}
                    <div class="grid gap-6 p-6 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Cheque Number</label>
                            <p class="mt-1 text-lg font-extrabold text-navy">{{ $cheque->cheque_no }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank Name</label>
                            <p class="mt-1 text-lg font-extrabold text-navy">{{ $cheque->bank_name }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Cheque Date</label>
                            <p class="mt-1 text-base font-bold text-slate-700">{{ $cheque->cheque_date?->format('d M Y') }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Cheque Amount</label>
                            <p class="mt-1 text-xl font-black text-primary">{{ Currency::formatLkr($cheque->amount) }}</p>
                        </div>

                        @if($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED)
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Received From Customer</label>
                                <p class="mt-1 text-base font-bold text-navy">
                                    @if($cheque->customer)
                                        <a href="{{ route('customers.show', $cheque->customer_id) }}" class="text-primary hover:underline">{{ $cheque->customer->name }}</a>
                                        @if($cheque->customer->business_name)
                                            <span class="block text-xs font-medium text-slate-400">{{ $cheque->customer->business_name }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                        @elseif($cheque->cheque_type === Cheque::TYPE_OWN_ISSUED)
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Issued To Supplier</label>
                                <p class="mt-1 text-base font-bold text-navy">
                                    @if($cheque->supplier)
                                        <a href="{{ route('suppliers.show', $cheque->supplier_id) }}" class="text-primary hover:underline">{{ $cheque->supplier->name }}</a>
                                        @if($cheque->supplier->business_name)
                                            <span class="block text-xs font-medium text-slate-400">{{ $cheque->supplier->business_name }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Notes / Remarks</label>
                            <p class="mt-1 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 border border-slate-100 whitespace-pre-line">{{ $cheque->notes ?: 'No notes added to this cheque.' }}</p>
                        </div>

                        @if($cheque->attachment)
                            <div class="sm:col-span-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Attachment</label>
                                <div class="mt-2 flex items-center gap-3">
                                    <a href="{{ Storage::url($cheque->attachment) }}" target="_blank" class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                                        <i class="fa-solid fa-paperclip"></i>View File
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SPECIAL TRANSFER SECTION: Customer Cheque Given to Supplier --}}
                @if ($cheque->isOwnIssued() && $cheque->supplier_cheque_mode === 'received_customer_cheque')
                    <div class="rounded-3xl bg-white p-6 shadow-soft border border-amber-100">
                        <h4 class="mb-4 flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide text-amber-700">
                            <i class="fa-solid fa-share"></i>Customer Cheque Given to Supplier
                        </h4>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Original Customer</span>
                                <p class="mt-1 font-bold text-navy">
                                    @if($cheque->originalCustomer)
                                        <a href="{{ route('customers.show', $cheque->original_customer_id) }}" class="text-primary hover:underline">{{ $cheque->originalCustomer->name }}</a>
                                    @else
                                        {{ $cheque->sourceCustomerCheque?->customer?->name ?? $cheque->customer?->name ?? '—' }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Given To Supplier</span>
                                <p class="mt-1 font-bold text-navy">
                                    @if($cheque->supplier)
                                        <a href="{{ route('suppliers.show', $cheque->supplier_id) }}" class="text-primary hover:underline">{{ $cheque->supplier->name }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Original Cheque No</span>
                                <p class="mt-1 font-bold text-navy">{{ $cheque->sourceCustomerCheque?->cheque_no ?? $cheque->cheque_no }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank</span>
                                <p class="mt-1 font-bold text-navy">{{ $cheque->bank_name }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Amount</span>
                                <p class="mt-1 font-black text-primary">{{ Currency::formatLkr($cheque->amount) }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Transfer Date</span>
                                <p class="mt-1 font-bold text-navy">{{ $cheque->transferred_date ? $cheque->transferred_date->format('d M Y') : '—' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800 whitespace-pre-line">
                            {{ $cheque->transfer_note ?: 'This supplier cheque was created from an existing customer received cheque.' }}
                        </div>

                        @if ($cheque->source_customer_cheque_id)
                            <div class="mt-5">
                                <a href="{{ route('cheques.show', $cheque->source_customer_cheque_id) }}" class="inline-flex items-center gap-2 rounded-2xl bg-primary px-4 py-2.5 text-xs font-bold text-white transition hover:bg-blue-700">
                                    <i class="fa-solid fa-up-right-from-square"></i>View Original Customer Cheque
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- SPECIAL TRANSFER SECTION: Customer Cheque Given to Supplier (From Customer Side View) --}}
                @if ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                    <div class="rounded-3xl bg-white p-6 shadow-soft border border-amber-100">
                        <h4 class="mb-4 text-sm font-extrabold uppercase tracking-wide text-amber-700 flex items-center gap-2">
                            <i class="fa-solid fa-share"></i>Transfer Information
                        </h4>
                        <p class="mb-5 text-sm text-slate-600">
                            This cheque was given to supplier <strong class="text-navy">{{ $cheque->givenToSupplier?->name ?? '—' }}</strong>
                            on <strong class="text-navy">{{ $cheque->transferred_date ? $cheque->transferred_date->format('d M Y') : '—' }}</strong>.
                        </p>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <dt class="text-slate-500">Transferred to Supplier</dt>
                                    <dd class="font-bold text-navy">
                                        @if($cheque->givenToSupplier)
                                            <a href="{{ route('suppliers.show', $cheque->given_to_supplier_id) }}" class="text-primary hover:underline">{{ $cheque->givenToSupplier->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <dt class="text-slate-500">Transfer Date</dt>
                                    <dd class="font-bold text-slate-700">{{ $cheque->transferred_date ? $cheque->transferred_date->format('d M Y') : '—' }}</dd>
                                </div>
                                @if ($transferredToCheque)
                                    <div class="pt-2">
                                        <a href="{{ route('cheques.show', $transferredToCheque->id) }}" class="inline-flex items-center gap-2 text-xs font-bold text-primary hover:underline">
                                            <i class="fa-solid fa-up-right-from-square"></i>View Supplier Cheque Record
                                        </a>
                                    </div>
                                @endif
                            </dl>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Transfer Note</dt>
                                <dd class="text-sm text-slate-600 bg-amber-50/50 rounded-2xl p-3 border border-amber-100/50 whitespace-pre-line">{{ $cheque->transfer_note ?: 'No transfer note provided.' }}</dd>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Column: Activity History & Audit Logs --}}
            <div class="space-y-6">
                <div class="overflow-hidden rounded-3xl bg-white shadow-soft">
                    {{-- Tabs Header --}}
                    <div class="flex border-b border-slate-100 bg-slate-50 p-2">
                        <button type="button" onclick="switchHistoryTab('timeline')" id="tabBtn-timeline" class="flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-primary bg-white shadow-sm transition">
                            <i class="fa-solid fa-clock-rotate-left mr-2"></i>Timeline
                        </button>
                        <button type="button" onclick="switchHistoryTab('audits')" id="tabBtn-audits" class="flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-slate-500 hover:text-navy transition">
                            <i class="fa-solid fa-shield-halved mr-2"></i>Audit Trail
                        </button>
                    </div>

                    {{-- Tab 1: Timeline Content --}}
                    <div id="tabContent-timeline" class="p-6 space-y-6">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Activity Timeline</h4>
                        
                        @if ($historyEvents->isEmpty())
                            <p class="text-sm text-slate-400 text-center py-6">No activity recorded yet.</p>
                        @else
                            <div class="relative border-l border-slate-100 pl-6 ml-3 space-y-8">
                                @foreach ($historyEvents as $event)
                                    @php
                                        // Custom colors & icons per action
                                        $iconClass = 'fa-circle-dot';
                                        $bulletClass = 'bg-slate-400 text-white';
                                        
                                        if ($event['action'] === 'created') {
                                            $iconClass = 'fa-plus';
                                            $bulletClass = 'bg-emerald-500 text-white ring-8 ring-emerald-50';
                                        } elseif ($event['action'] === 'transferred') {
                                            $iconClass = 'fa-share';
                                            $bulletClass = 'bg-amber-500 text-white ring-8 ring-amber-50';
                                        } elseif ($event['action'] === 'approved') {
                                            $iconClass = 'fa-shield-check';
                                            $bulletClass = 'bg-blue-500 text-white ring-8 ring-blue-50';
                                        } elseif ($event['action'] === 'status_changed') {
                                            $bulletClass = match($event['new_status']) {
                                                Cheque::STATUS_PASSED => 'bg-emerald-500 text-white ring-8 ring-emerald-50',
                                                Cheque::STATUS_RETURNED => 'bg-red-500 text-white ring-8 ring-red-50',
                                                Cheque::STATUS_DEPOSITED => 'bg-sky-500 text-white ring-8 ring-sky-50',
                                                Cheque::STATUS_HOLD => 'bg-gray-500 text-white ring-8 ring-gray-50',
                                                Cheque::STATUS_CANCELLED => 'bg-slate-500 text-white ring-8 ring-slate-50',
                                                default => 'bg-orange-500 text-white ring-8 ring-orange-50',
                                            };
                                            $iconClass = match($event['new_status']) {
                                                Cheque::STATUS_PASSED => 'fa-check',
                                                Cheque::STATUS_RETURNED => 'fa-rotate-left',
                                                Cheque::STATUS_DEPOSITED => 'fa-building-columns',
                                                Cheque::STATUS_HOLD => 'fa-pause',
                                                Cheque::STATUS_CANCELLED => 'fa-xmark',
                                                default => 'fa-clock',
                                            };
                                        }
                                    @endphp
                                    <div class="relative">
                                        {{-- Bullet dot with absolute placement left --}}
                                        <span class="absolute -left-[37px] top-0 flex h-7 w-7 items-center justify-center rounded-full text-xs font-black {{ $bulletClass }}">
                                            <i class="fa-solid {{ $iconClass }} text-[9px]"></i>
                                        </span>
                                        
                                        {{-- Event info --}}
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-[9px] font-extrabold uppercase tracking-wide px-2 py-0.5 rounded {{ $event['type'] === 'parent' ? 'bg-teal/10 text-teal' : 'bg-primary/10 text-primary' }}">
                                                    {{ $event['label'] }}
                                                </span>
                                                <span class="text-xs text-slate-400 font-medium">
                                                    {{ $event['date']->format('d M Y - h:i A') }}
                                                </span>
                                            </div>
                                            
                                            <h5 class="text-sm font-bold text-navy leading-snug">
                                                @if ($event['action'] === 'created')
                                                    Cheque received from customer <span class="font-extrabold text-slate-700">{{ $cheque->customer?->name ?? 'Unknown Customer' }}</span>
                                                @elseif ($event['action'] === 'transferred')
                                                    Cheque given to supplier <span class="font-extrabold text-amber-600">{{ $cheque->givenToSupplier?->name ?? 'Unknown Supplier' }}</span>
                                                @elseif ($event['action'] === 'approved')
                                                    Cheque approved
                                                @elseif ($event['action'] === 'status_changed')
                                                    Status changed to <span class="font-extrabold text-slate-700">{{ ChequePresentation::statusLabel($event['new_status']) }}</span>
                                                    @if ($event['old_status'])
                                                        <span class="text-xs text-slate-400 font-normal">(was {{ ChequePresentation::statusLabel($event['old_status']) }})</span>
                                                    @endif
                                                @else
                                                    Action: {{ ucfirst($event['action']) }}
                                                @endif
                                            </h5>
                                            
                                            <p class="text-[11px] text-slate-400">
                                                By: <span class="font-bold text-slate-600">{{ $event['user'] }}</span>
                                            </p>
                                            
                                            @if (filled($event['note']))
                                                <div class="mt-1.5 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-600 border border-slate-100/50 whitespace-pre-line italic">
                                                    "{{ $event['note'] }}"
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Tab 2: Audit Logs Content --}}
                    <div id="tabContent-audits" class="hidden p-6 space-y-6">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Technical Audit Trail</h4>
                        
                        @if ($mergedAudits->isEmpty())
                            <p class="text-sm text-slate-400 text-center py-6">No audits recorded.</p>
                        @else
                            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
                                @foreach ($mergedAudits as $log)
                                    <div class="rounded-2xl border border-slate-100 p-4 text-xs space-y-2 hover:bg-slate-50 transition">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-navy uppercase">{{ str_replace('_', ' ', $log['action']) }}</span>
                                            <span class="text-slate-400">{{ $log['date']->format('d M Y - h:i A') }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1 text-[11px] text-slate-500">
                                            <div>User: <strong class="text-slate-700 font-semibold">{{ $log['user'] }}</strong></div>
                                            <div class="text-right">Record: <strong class="text-slate-700 font-semibold">{{ $log['label'] }}</strong></div>
                                            <div>IP: <strong class="text-slate-700 font-semibold">{{ $log['ip'] ?: '—' }}</strong></div>
                                            <div class="text-right truncate" title="{{ $log['device'] }}">Device: <strong class="text-slate-700 font-semibold">{{ $log['device'] ? explode(' ', $log['device'])[0] : '—' }}</strong></div>
                                        </div>
                                        @if ($log['note'])
                                            <div class="bg-slate-100/50 p-2 rounded-xl text-[11px] text-slate-600 italic">
                                                {{ $log['note'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SMS Send Modal --}}
    <div id="smsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 lg:p-8">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closeSmsModal()"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-extrabold text-navy">Send SMS</h3>
                    <p class="text-xs text-slate-500">Cheque: <strong id="smsModalChequeNo">—</strong></p>
                </div>
                <button type="button" onclick="closeSmsModal()" class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
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

        function switchHistoryTab(tab) {
            const timelineBtn = document.getElementById('tabBtn-timeline');
            const auditsBtn = document.getElementById('tabBtn-audits');
            const timelineContent = document.getElementById('tabContent-timeline');
            const auditsContent = document.getElementById('tabContent-audits');

            if (tab === 'timeline') {
                timelineBtn.className = 'flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-primary bg-white shadow-sm transition';
                auditsBtn.className = 'flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-slate-500 hover:text-navy transition';
                timelineContent.classList.remove('hidden');
                auditsContent.classList.add('hidden');
            } else {
                auditsBtn.className = 'flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-primary bg-white shadow-sm transition';
                timelineBtn.className = 'flex-1 rounded-2xl py-2.5 text-center text-sm font-bold text-slate-500 hover:text-navy transition';
                timelineContent.classList.add('hidden');
                auditsContent.classList.remove('hidden');
            }
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSmsModal(); });
    </script>
@endpush
