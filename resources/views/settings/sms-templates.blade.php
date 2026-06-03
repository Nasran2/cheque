@extends('layouts.app')

@section('title', 'SMS Templates - Cheque Management System')
@section('page_title', 'SMS Templates')
@section('mobile_title', 'SMS Templates')

@section('content')
    {{-- Flash --}}
    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            <i class="fa-solid fa-circle-check text-emerald-500"></i>{{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">

        {{-- ── Sidebar ────────────────────────────────────────────────── --}}
        <aside class="hidden rounded-3xl bg-white p-4 shadow-soft lg:block">
            <h3 class="px-3 py-2 text-sm font-extrabold text-navy">Settings Sections</h3>
            <a href="{{ route('settings.index') }}#general"             class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">General</a>
            <a href="{{ route('settings.index') }}#cheque_rules"        class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cheque Rules</a>
            <a href="{{ route('settings.index') }}#customer_reminders"  class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Customer Reminders</a>
            <a href="{{ route('settings.index') }}#supplier_reminders"  class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Supplier Reminders</a>
            <a href="{{ route('settings.index') }}#notifications"       class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Notifications</a>
            <a href="{{ route('settings.sms') }}"                       class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">SMS Settings</a>
            <a href="{{ route('settings.sms.templates') }}"             class="block rounded-2xl bg-primary/10 px-3 py-3 text-sm font-bold text-primary">SMS Templates</a>
            <a href="{{ route('settings.index') }}#pdf"                 class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">PDF / Letterhead</a>
            <a href="{{ route('settings.index') }}#banks_permissions"   class="block rounded-2xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Banks &amp; Permissions</a>
        </aside>

        {{-- ── Content ────────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">{{ $templates->count() }} templates • Click <strong>Edit</strong> to customise message</p>
                </div>
                <a href="{{ route('settings.sms') }}" class="flex items-center gap-2 rounded-2xl bg-primary/10 px-4 py-2 text-sm font-bold text-primary hover:bg-primary/20">
                    <i class="fa-solid fa-gear"></i>SMS Settings
                </a>
            </div>

            {{-- ── Variables Help Box ──────────────────────────────────── --}}
            <section class="rounded-3xl border border-primary/20 bg-primary/5 p-5">
                <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-primary">
                    <i class="fa-solid fa-code"></i>Available Template Variables
                </h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($variables as $var)
                        <button type="button"
                            onclick="copyVariable('{{ $var }}')"
                            class="variable-chip rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-primary shadow-sm transition hover:bg-primary hover:text-white">
                            {{ $var }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Click a variable to copy it. Paste it into any template message. <code class="rounded bg-white px-1">{amount}</code> → <strong>Rs 125,000.00</strong> &nbsp;|&nbsp; <code class="rounded bg-white px-1">{cheque_date}</code> → <strong>20 May 2026</strong>
                </p>
            </section>

            {{-- ── Template Cards Grid ─────────────────────────────────── --}}
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($templates as $template)
                    <div class="group relative rounded-3xl bg-white p-5 shadow-soft transition hover:shadow-lg" id="card-{{ $template->id }}">

                        {{-- Card Header --}}
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h4 class="text-base font-extrabold text-navy">{{ $template->template_name }}</h4>
                                <code class="mt-0.5 inline-block rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">{{ $template->template_key }}</code>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                {{-- Status badge --}}
                                <span id="badge-{{ $template->id }}"
                                    class="rounded-full px-3 py-1 text-xs font-bold {{ $template->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $template->isActive() ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        {{-- Message Preview --}}
                        <p class="mb-4 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $template->message }}</p>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 border-t border-slate-100 pt-3">
                            <button type="button"
                                onclick="openEditModal({{ $template->id }}, '{{ addslashes($template->template_name) }}', {{ $template->status === 'active' ? 'true' : 'false' }}, {{ json_encode($template->message) }})"
                                class="flex items-center gap-2 rounded-xl bg-primary/10 px-4 py-2 text-xs font-bold text-primary transition hover:bg-primary hover:text-white">
                                <i class="fa-solid fa-pen"></i>Edit
                            </button>
                            <button type="button"
                                onclick="toggleTemplate({{ $template->id }})"
                                id="toggleBtn-{{ $template->id }}"
                                class="flex items-center gap-2 rounded-xl {{ $template->isActive() ? 'bg-red-50 text-red-600 hover:bg-red-500 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white' }} px-4 py-2 text-xs font-bold transition">
                                <i class="fa-solid {{ $template->isActive() ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                {{ $template->isActive() ? 'Disable' : 'Enable' }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- ── Edit Modal ────────────────────────────────────────────────────── --}}
    <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 lg:p-8" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closeEditModal()"></div>

        <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-extrabold text-navy" id="modalTitle">Edit Template</h3>
                    <p class="text-xs text-slate-500" id="modalKey"></p>
                </div>
                <button type="button" onclick="closeEditModal()" class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <form id="editForm" method="POST">
                @csrf
                @method('POST')

                <div class="space-y-4 px-6 py-5">
                    {{-- Template Name --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold text-navy">Template Name</label>
                        <input type="text" name="template_name" id="modalTemplateName"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>

                    {{-- Message --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-bold text-navy">Message</label>
                            <span id="charCount" class="text-xs text-slate-400">0 / 500 chars</span>
                        </div>
                        <textarea name="message" id="modalMessage" rows="5"
                            oninput="updateCharCount(this)"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm leading-relaxed outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>
                        <p class="mt-1 text-xs text-slate-400">Use variables from the list above. Click a variable chip to copy it.</p>
                    </div>

                    {{-- Quick Variables --}}
                    <div>
                        <p class="mb-2 text-xs font-bold text-slate-500">Quick Insert Variables:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['{customer_name}', '{supplier_name}', '{cheque_no}', '{amount}', '{cheque_date}', '{return_reason}', '{overdue_days}', '{company_name}'] as $qv)
                                <button type="button"
                                    onclick="insertVar('{{ $qv }}')"
                                    class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-primary hover:bg-primary hover:text-white transition">{{ $qv }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold text-navy">Status</label>
                        <div class="flex gap-3">
                            <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 border-transparent bg-slate-50 px-4 py-3 transition has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                                <input type="radio" name="status" value="active" class="accent-emerald-500">
                                <div>
                                    <p class="text-sm font-bold text-navy">Active</p>
                                    <p class="text-xs text-slate-500">Template will be used</p>
                                </div>
                            </label>
                            <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 border-transparent bg-slate-50 px-4 py-3 transition has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
                                <input type="radio" name="status" value="inactive" class="accent-red-500">
                                <div>
                                    <p class="text-sm font-bold text-navy">Inactive</p>
                                    <p class="text-xs text-slate-500">Template will be skipped</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <button type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-primary py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                        <i class="fa-solid fa-floppy-disk"></i>Save Template
                    </button>
                    <button type="button"
                        onclick="sendTemplateTestSms()"
                        class="flex items-center gap-2 rounded-2xl bg-purplePay/10 px-5 py-3 text-sm font-bold text-purplePay transition hover:bg-purplePay hover:text-white">
                        <i class="fa-solid fa-paper-plane"></i>Test
                    </button>
                    <button type="button" onclick="closeEditModal()"
                        class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-200">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Test SMS Modal ────────────────────────────────────────────────── --}}
    <div id="testModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50" onclick="closeTestModal()"></div>
        <div class="relative z-10 w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <h3 class="mb-4 text-base font-extrabold text-navy">Send Test SMS</h3>
            <div class="mb-3">
                <label class="mb-2 block text-sm font-bold text-navy">Phone Number</label>
                <input type="text" id="tplTestPhone" placeholder="e.g. 0771234567"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            </div>
            <div id="tplTestResult" class="mb-3 hidden rounded-2xl px-4 py-3 text-sm font-semibold"></div>
            <div class="flex gap-3">
                <button type="button" onclick="doTemplateTest()"
                    class="flex-1 rounded-2xl bg-purplePay py-3 text-sm font-bold text-white transition hover:bg-purple-700">
                    <i class="fa-solid fa-paper-plane mr-2"></i>Send
                </button>
                <button type="button" onclick="closeTestModal()"
                    class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-600">Cancel</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let currentTemplateId = null;

    // ── Open / Close Edit Modal ──────────────────────────────────────────────
    function openEditModal(id, name, isActive, message) {
        currentTemplateId = id;
        document.getElementById('modalTitle').textContent = 'Edit: ' + name;
        document.getElementById('modalTemplateName').value = name;
        document.getElementById('modalMessage').value = message;
        document.getElementById('charCount').textContent = message.length + ' / 500 chars';

        const form = document.getElementById('editForm');
        form.action = `/settings/sms-templates/${id}`;

        // Set radio
        const radios = document.querySelectorAll('input[name="status"]');
        radios.forEach(r => r.checked = (r.value === (isActive ? 'active' : 'inactive')));

        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
        document.getElementById('modalMessage').focus();
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }

    // ── Toggle Template Status ───────────────────────────────────────────────
    async function toggleTemplate(id) {
        try {
            const res = await fetch(`/settings/sms-templates/${id}/toggle`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (data.success) {
                const badge = document.getElementById(`badge-${id}`);
                const btn   = document.getElementById(`toggleBtn-${id}`);
                const isNowActive = data.status === 'active';

                badge.textContent = isNowActive ? 'Active' : 'Inactive';
                badge.className = `rounded-full px-3 py-1 text-xs font-bold ${isNowActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`;

                btn.innerHTML  = `<i class="fa-solid ${isNowActive ? 'fa-toggle-off' : 'fa-toggle-on'}"></i> ${isNowActive ? 'Disable' : 'Enable'}`;
                btn.className  = `flex items-center gap-2 rounded-xl ${isNowActive ? 'bg-red-50 text-red-600 hover:bg-red-500 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white'} px-4 py-2 text-xs font-bold transition`;
            }
        } catch (e) {
            alert('Failed to toggle template status.');
        }
    }

    // ── Variable Insert ──────────────────────────────────────────────────────
    function insertVar(variable) {
        const ta = document.getElementById('modalMessage');
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + variable + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = start + variable.length;
        ta.focus();
        updateCharCount(ta);
    }

    function copyVariable(variable) {
        navigator.clipboard.writeText(variable).then(() => {
            // Brief visual feedback
            event.target.textContent = '✓ Copied!';
            setTimeout(() => event.target.textContent = variable, 1200);
        });
    }

    function updateCharCount(el) {
        document.getElementById('charCount').textContent = el.value.length + ' / 500 chars';
    }

    // ── Template Test SMS ────────────────────────────────────────────────────
    function sendTemplateTestSms() {
        document.getElementById('tplTestPhone').value = '';
        document.getElementById('tplTestResult').className = 'hidden';
        document.getElementById('testModal').classList.remove('hidden');
        document.getElementById('testModal').classList.add('flex');
    }

    function closeTestModal() {
        document.getElementById('testModal').classList.add('hidden');
        document.getElementById('testModal').classList.remove('flex');
    }

    async function doTemplateTest() {
        const phone   = document.getElementById('tplTestPhone').value.trim();
        const message = document.getElementById('modalMessage').value.trim();
        const result  = document.getElementById('tplTestResult');

        if (!phone) { showTplResult(false, 'Enter a phone number.'); return; }

        try {
            const res = await fetch('{{ route('settings.sms.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone, message }),
            });
            const data = await res.json();
            showTplResult(data.success, data.message);
        } catch (e) {
            showTplResult(false, 'Network error.');
        }
    }

    function showTplResult(success, message) {
        const el = document.getElementById('tplTestResult');
        el.className = `flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold ${
            success ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                    : 'bg-red-50 text-red-700 border border-red-100'
        }`;
        el.innerHTML = `<i class="fa-solid ${success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${message}`;
    }

    // Close modals on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeEditModal(); closeTestModal(); }
    });
</script>
@endpush
