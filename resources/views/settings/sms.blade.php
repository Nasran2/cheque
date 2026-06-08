@extends('layouts.app')

@section('title', 'SMS Settings - Cheque Management System')
@section('page_title', 'SMS Settings')
@section('mobile_title', 'SMS Settings')

@section('content')
    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            <i class="fa-solid fa-circle-check text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <i class="fa-solid fa-circle-xmark text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">

        {{-- ── Settings Sidebar ──────────────────────────────────────── --}}
        <aside class="hidden rounded-3xl bg-white p-4 shadow-soft lg:block">
            <h3 class="px-3 py-2 text-sm font-extrabold text-navy">Settings Sections</h3>
            <a href="{{ route('settings.index') }}#general"             class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">General</a>
            <a href="{{ route('settings.index') }}#cheque_rules"        class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cheque Rules</a>
            <a href="{{ route('settings.index') }}#customer_reminders"  class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Customer Reminders</a>
            <a href="{{ route('settings.index') }}#supplier_reminders"  class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Supplier Reminders</a>
            <a href="{{ route('settings.index') }}#notifications"       class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Notifications</a>
            <a href="{{ route('settings.sms') }}"                       class="block rounded-2xl bg-primary/10 px-3 py-3 text-sm font-bold text-primary">SMS Settings</a>
            <a href="{{ route('settings.sms.templates') }}"             class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">SMS Templates</a>
            <a href="{{ route('settings.index') }}#pdf"                 class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">PDF / Letterhead</a>
            <a href="{{ route('settings.index') }}#banks_permissions"   class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Banks &amp; Permissions</a>
        </aside>

        {{-- ── Main Content ──────────────────────────────────────────── --}}
        <div class="space-y-5">

            <form method="POST" action="{{ route('settings.sms.update') }}" id="smsSettingsForm">
                @csrf

                {{-- ─── Gateway Settings ─────────────────────────────── --}}
                <section class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10">
                            <i class="fa-solid fa-tower-broadcast text-primary"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-navy">SMS Gateway Settings</h3>
                            <p class="text-xs text-slate-500">Configure Textit.biz SMS API credentials</p>
                        </div>
                    </div>

                    {{-- Enable Toggle --}}
                    <div class="mb-5 flex items-center justify-between rounded-2xl bg-slate-50 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-navy">Enable SMS Gateway</p>
                            <p class="text-xs text-slate-500">Activate SMS sending via Textit.biz</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="sms_enabled" value="0">
                            <input type="checkbox" name="sms_enabled" value="1" id="sms_enabled"
                                {{ ($settings['sms_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                                class="peer sr-only">
                            <div class="peer h-7 w-12 rounded-full bg-slate-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-primary peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        {{-- Provider --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">SMS Provider</label>
                            <select name="sms_provider" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="textit" {{ ($settings['sms_provider'] ?? 'textit') === 'textit' ? 'selected' : '' }}>textit.biz</option>
                            </select>
                        </div>

                        {{-- HTTP Method --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">HTTP Method</label>
                            <select name="sms_method" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="GET"  {{ ($settings['sms_method'] ?? 'GET') === 'GET'  ? 'selected' : '' }}>GET</option>
                                <option value="POST" {{ ($settings['sms_method'] ?? 'GET') === 'POST' ? 'selected' : '' }}>POST</option>
                            </select>
                        </div>

                        {{-- User ID --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">Textit User ID / Phone Number</label>
                            <input type="text" name="textit_user_id"
                                value="{{ $settings['textit_user_id'] ?? '' }}"
                                placeholder="e.g. 94771234567"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                            <p class="mt-1 text-xs text-slate-400">International format without +</p>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">
                                Textit Password
                                <span class="ml-2 text-xs font-normal text-slate-400">Leave blank to keep current</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="textit_password_new" id="textitPassword"
                                    placeholder="Enter new password to update"
                                    autocomplete="new-password"
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <button type="button" onclick="togglePasswordVisibility()"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                    <i class="fa-regular fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            @if (!empty($settings['textit_password']))
                                <p class="mt-1 text-xs text-emerald-600"><i class="fa-solid fa-lock mr-1"></i>Password is saved and encrypted</p>
                            @else
                                <p class="mt-1 text-xs text-amber-600"><i class="fa-solid fa-triangle-exclamation mr-1"></i>No password saved yet</p>
                            @endif
                        </div>

                        {{-- Base URL --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">API Base URL</label>
                            <input type="url" name="textit_base_url"
                                value="{{ $settings['textit_base_url'] ?? 'https://textit.biz/sendmsg' }}"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        {{-- Ref Prefix --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">Sender Reference Prefix</label>
                            <input type="text" name="sms_ref_prefix"
                                value="{{ $settings['sms_ref_prefix'] ?? 'CHEQUE' }}"
                                maxlength="10"
                                placeholder="e.g. CHEQUE"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                            <p class="mt-1 text-xs text-slate-400">Max 10 chars. Combined with cheque no (max 15).</p>
                        </div>

                        {{-- Daily SMS Time --}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-navy">Daily SMS Send Time</label>
                            <input type="time" name="daily_sms_time"
                                value="{{ $settings['daily_sms_time'] ?? '09:00' }}"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                    </div>
                </section>

                {{-- ─── SMS Event Toggles ─────────────────────────────── --}}
                <section class="rounded-3xl bg-white p-5 shadow-soft">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal/10">
                            <i class="fa-solid fa-bell text-teal"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-navy">SMS Event Triggers</h3>
                            <p class="text-xs text-slate-500">Choose which events automatically send SMS</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @php
                            $toggles = [
                                'received_cheque_sms_enabled'   => ['Received Cheque SMS',        'Send SMS when cheque is added',        'fa-hand-holding-dollar', 'text-indigo-500'],
                                'customer_reminder_sms_enabled' => ['Customer Cheque Reminders',  'Send SMS when customer cheque is due', 'fa-users', 'text-primary'],
                                'supplier_reminder_sms_enabled' => ['Supplier Cheque Reminders',  'Send SMS when own cheque is due',      'fa-truck-field', 'text-teal'],
                                'returned_cheque_sms_enabled'   => ['Returned Cheque SMS',        'Send SMS when cheque is returned',     'fa-rotate-left', 'text-danger'],
                                'passed_cheque_sms_enabled'     => ['Passed Cheque SMS',          'Send SMS when cheque is passed',       'fa-circle-check', 'text-success'],
                                'overdue_cheque_sms_enabled'    => ['Overdue Cheque SMS',         'Send SMS for overdue cheques',         'fa-clock', 'text-warning'],
                            ];
                        @endphp

                        @foreach ($toggles as $key => [$label, $desc, $icon, $iconColor])
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                                        <i class="fa-solid {{ $icon }} {{ $iconColor }} text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-navy">{{ $label }}</p>
                                        <p class="text-xs text-slate-500">{{ $desc }}</p>
                                    </div>
                                </div>
                                <div class="relative shrink-0">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input type="checkbox" name="{{ $key }}" value="1"
                                        {{ ($settings[$key] ?? '0') === '1' ? 'checked' : '' }}
                                        class="peer sr-only">
                                    <div class="peer h-7 w-12 rounded-full bg-slate-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-primary peer-checked:after:translate-x-5"></div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-2xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>Save SMS Settings
                    </button>
                </div>
            </form>

            {{-- ─── Test SMS Card ─────────────────────────────────────── --}}
            <section class="rounded-3xl bg-white p-5 shadow-soft">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purplePay/10">
                        <i class="fa-solid fa-paper-plane text-purplePay"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-navy">Send Test SMS</h3>
                        <p class="text-xs text-slate-500">Verify your SMS gateway is working</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-navy">Test Phone Number</label>
                        <input type="text" id="testPhone" placeholder="e.g. 0771234567 or 94771234567"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-navy">Test Message</label>
                        <input type="text" id="testMessage"
                            value="This is a test SMS from Cheque Management System. Powered by Twinsofte.com"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="button" id="sendTestBtn"
                        onclick="sendTestSms()"
                        class="flex items-center gap-2 rounded-2xl bg-purplePay px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purplePay/20 transition hover:bg-purple-700">
                        <i class="fa-solid fa-paper-plane"></i>Send Test SMS
                    </button>
                    <div id="testResult" class="hidden items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold"></div>
                </div>
            </section>

            {{-- ─── Recent SMS Logs ───────────────────────────────────── --}}
            <section class="rounded-3xl bg-white p-5 shadow-soft">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-navy/10">
                            <i class="fa-solid fa-list-check text-navy"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-navy">Recent SMS Logs</h3>
                            <p class="text-xs text-slate-500">Last 10 SMS messages sent from this system</p>
                        </div>
                    </div>
                    <button type="button" onclick="loadLogs()" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200">
                        <i class="fa-solid fa-rotate-right mr-1"></i>Refresh
                    </button>
                </div>

                <div id="smsLogsContainer">
                    @include('settings.partials.sms-logs-table', ['recentLogs' => $recentLogs])
                </div>
            </section>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ── Toggle password visibility ─────────────────────────────────────────
    function togglePasswordVisibility() {
        const input = document.getElementById('textitPassword');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-regular fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa-regular fa-eye';
        }
    }

    // ── Send Test SMS ──────────────────────────────────────────────────────
    async function sendTestSms() {
        const btn    = document.getElementById('sendTestBtn');
        const result = document.getElementById('testResult');
        const phone  = document.getElementById('testPhone').value.trim();
        const msg    = document.getElementById('testMessage').value.trim();

        if (!phone || !msg) {
            showResult(false, 'Please enter a phone number and message.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Sending…';
        result.className = 'hidden';

        try {
            const response = await fetch('{{ route('settings.sms.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone, message: msg }),
            });

            const data = await response.json();
            showResult(data.success, data.message);
        } catch (e) {
            showResult(false, 'Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i>Send Test SMS';
        }
    }

    function showResult(success, message) {
        const el = document.getElementById('testResult');
        el.className = `flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold ${
            success ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                    : 'bg-red-50 text-red-700 border border-red-100'
        }`;
        el.innerHTML = `<i class="fa-solid ${success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${message}`;
    }

    // ── Load SMS Logs ──────────────────────────────────────────────────────
    async function loadLogs() {
        const container = document.getElementById('smsLogsContainer');
        container.innerHTML = '<div class="flex items-center justify-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading…</div>';

        try {
            const response = await fetch('{{ route('settings.sms.logs') }}', {
                headers: { 'Accept': 'application/json' }
            });
            const logs = await response.json();

            if (!logs.length) {
                container.innerHTML = '<div class="py-8 text-center text-sm text-slate-400">No SMS logs yet.</div>';
                return;
            }

            container.innerHTML = renderLogsTable(logs.slice(0, 10));
        } catch (e) {
            container.innerHTML = '<div class="py-4 text-center text-sm text-red-500">Failed to load logs.</div>';
        }
    }

    function renderLogsTable(logs) {
        const statusBadge = (s) => {
            const map = {
                sent:    'bg-emerald-100 text-emerald-700',
                failed:  'bg-red-100 text-red-700',
                pending: 'bg-amber-100 text-amber-700',
            };
            return `<span class="rounded-full px-2 py-0.5 text-xs font-bold ${map[s] ?? 'bg-slate-100 text-slate-600'}">${s}</span>`;
        };

        let rows = logs.map(log => `
            <tr class="border-b border-slate-100 last:border-0">
                <td class="py-3 pr-4 text-sm font-semibold text-navy">${log.phone}</td>
                <td class="py-3 pr-4 text-xs text-slate-600 max-w-xs truncate">${log.message}</td>
                <td class="py-3 pr-4">${statusBadge(log.status)}</td>
                <td class="py-3 text-xs text-slate-400">${log.sent_at ?? log.created_at}</td>
            </tr>
        `).join('');

        return `<div class="overflow-x-auto"><table class="w-full">
            <thead><tr class="border-b border-slate-200">
                <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Phone</th>
                <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Message</th>
                <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Status</th>
                <th class="pb-2 text-left text-xs font-bold text-slate-500">Sent At</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;
    }
</script>
@endpush
