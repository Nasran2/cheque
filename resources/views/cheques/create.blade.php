@extends('layouts.app')

@php
    use App\Models\Cheque;
@endphp

@section('title', 'Add Cheque - Cheque Management System')
@section('page_title', 'Add New Cheque')
@section('mobile_title', 'Add Cheque')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-wrapper .ts-control {
            min-height: 48px;
            border-radius: 1rem !important;
            border-color: #e2e8f0 !important;
            padding: .65rem 1rem !important;
            font-size: .875rem !important;
            box-shadow: none !important;
        }

        .ts-wrapper.focus .ts-control {
            border-color: #0B5CFF !important;
            box-shadow: 0 0 0 4px rgba(11, 92, 255, .1) !important;
        }

        .ts-dropdown {
            z-index: 50 !important;
            border-radius: 1rem !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .12) !important;
            overflow: hidden;
        }

        .ts-dropdown .option {
            padding: .75rem 1rem !important;
        }

        .ts-dropdown .active {
            background: #0B5CFF !important;
            color: #fff !important;
        }

        /* Custom Searchable Select Fallback Styles */
        .custom-select-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-radius: 0.75rem;
            transition: all 0.15s ease-in-out;
            color: #061A3A;
        }
        .custom-select-option:hover {
            background-color: #0B5CFF !important;
            color: #fff !important;
        }
        .custom-select-option:hover .text-navy,
        .custom-select-option:hover .text-slate-500,
        .custom-select-option:hover .text-slate-400,
        .custom-select-option:hover div {
            color: #fff !important;
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-5">
            <a href="{{ route('cheques.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition hover:text-navy">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Cheques List
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-3xl bg-white p-5 shadow-soft sm:p-8">
            <div class="mb-6 flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <h3 class="text-xl font-extrabold text-navy">New Cheque Details</h3>
                    <p class="mt-1 text-sm text-slate-500">Record customer cheques or supplier issued cheques.</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <i class="fa-solid fa-money-check text-xl"></i>
                </div>
            </div>

            <form method="POST" action="{{ route('cheques.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                        Cheque Type <span class="text-danger">*</span>
                    </label>
                    <select name="cheque_type" id="chequeType" onchange="window.syncChequeForm && window.syncChequeForm()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10" required>
                        <option value="{{ Cheque::TYPE_CUSTOMER_RECEIVED }}" @selected(old('cheque_type') === Cheque::TYPE_CUSTOMER_RECEIVED)>Received From Customer</option>
                        <option value="{{ Cheque::TYPE_OWN_ISSUED }}" @selected(old('cheque_type') === Cheque::TYPE_OWN_ISSUED)>Issued to Supplier</option>
                    </select>
                    <p id="chequeTypeHelp" class="mt-1.5 text-xs text-slate-400">Record a cheque received from a customer.</p>
                </div>

                <div id="customerSelectContainer">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                        Searchable Customer <span class="text-danger">*</span>
                    </label>
                    <select name="customer_id" id="customerSelect" class="w-full">
                        <option value="">Search and select customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                {{ $customer->name }}{{ $customer->business_name ? ' - ' . $customer->business_name : '' }}{{ $customer->phone ? ' (' . $customer->phone . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="supplierSelectContainer" class="hidden">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                        Searchable Supplier <span class="text-danger">*</span>
                    </label>
                    <select name="supplier_id" id="supplierSelect" class="w-full">
                        <option value="">Search and select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                {{ $supplier->name }}{{ $supplier->business_name ? ' - ' . $supplier->business_name : '' }}{{ $supplier->phone ? ' (' . $supplier->phone . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <section id="supplierModeContainer" class="hidden rounded-3xl border border-slate-100 bg-slate-50 p-4 sm:p-5">
                    <h4 class="text-sm font-extrabold text-navy">Supplier Cheque Option</h4>
                    <p class="mt-1 text-sm text-slate-500">Choose how you are paying this supplier.</p>
                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary/40">
                            <input type="radio" name="supplier_cheque_mode" value="own_cheque" class="mt-1 text-primary focus:ring-primary" @checked(old('supplier_cheque_mode', 'own_cheque') === 'own_cheque')>
                            <span>
                                <span class="block text-sm font-bold text-navy">No, issue our own cheque</span>
                                <span class="mt-1 block text-xs text-slate-500">Enter supplier cheque details manually.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary/40">
                            <input type="radio" name="supplier_cheque_mode" value="received_customer_cheque" class="mt-1 text-primary focus:ring-primary" @checked(old('supplier_cheque_mode') === 'received_customer_cheque')>
                            <span>
                                <span class="block text-sm font-bold text-navy">Yes, give already received customer cheque</span>
                                <span class="mt-1 block text-xs text-slate-500">Select an available customer cheque and auto-fill details.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary/40">
                            <input type="radio" name="supplier_cheque_mode" value="combined_cheques" class="mt-1 text-primary focus:ring-primary" @checked(old('supplier_cheque_mode') === 'combined_cheques')>
                            <span>
                                <span class="block text-sm font-bold text-navy">Combine one or more cheques</span>
                                <span class="mt-1 block text-xs text-slate-500">Use own cheques and/or customer received cheques together.</span>
                            </span>
                        </label>
                    </div>
                </section>

                <div id="sourceChequeContainer" class="hidden">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                        Search Existing Customer Received Cheque <span class="text-danger">*</span>
                    </label>
                    <input type="hidden" name="source_customer_cheque_id" id="sourceChequeIdInput" value="{{ old('source_customer_cheque_id') }}">
                    <select name="_source_customer_cheque_id" id="sourceChequeSelect" class="w-full">
                        <option value="">Search existing customer received cheque</option>
                    </select>
                    <p class="mt-2 text-xs text-slate-400">Only pending, deposited, and hold customer cheques that are not already given to a supplier are shown.</p>
                </div>

                <section id="combinedChequesContainer" class="hidden rounded-3xl border border-slate-100 bg-slate-50 p-4 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-sm font-extrabold text-navy">Combined Supplier Cheques</h4>
                            <p class="mt-1 text-sm text-slate-500">Add one or more own cheques and/or customer received cheques for this supplier.</p>
                        </div>
                        <button type="button" id="addOwnChequeButton" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                            <i class="fa-solid fa-plus"></i>
                            Add Own Cheque
                        </button>
                    </div>

                    <div id="combinedOwnChequeRows" class="mt-4 space-y-4"></div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">Add Customer Received Cheque</label>
                        <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
                            <select name="_combined_customer_cheque" id="combinedSourceChequeSelect" class="w-full">
                                <option value="">Search existing customer received cheque</option>
                            </select>
                            <button type="button" id="addCustomerChequeButton" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-navy">
                                Add Customer Cheque
                            </button>
                        </div>
                        <div id="combinedCustomerChequeList" class="mt-4 space-y-3"></div>
                    </div>

                    <p class="mt-4 text-xs font-semibold text-slate-500">At least one own cheque or customer received cheque is required.</p>
                </section>

                <div id="transferInfoBox" class="hidden rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <div class="flex gap-3">
                        <i class="fa-solid fa-circle-info mt-0.5 text-amber-600"></i>
                        <p>
                            This cheque was originally received from <strong id="infoBoxCustomer">—</strong>.
                            You are now giving this cheque to supplier <strong id="infoBoxSupplier">—</strong>.
                        </p>
                    </div>
                </div>

                <div id="transferPreviewCard" class="hidden rounded-3xl border border-slate-100 bg-slate-50 p-5">
                    <h4 class="mb-4 flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                        <i class="fa-solid fa-money-check-dollar text-primary"></i>
                        Selected Cheque Preview
                    </h4>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Original Customer</span>
                            <p id="previewCustomer" class="mt-1 font-bold text-navy">—</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Cheque No</span>
                            <p id="previewChequeNo" class="mt-1 font-bold text-navy">—</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Bank</span>
                            <p id="previewBank" class="mt-1 font-bold text-navy">—</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Date</span>
                            <p id="previewDate" class="mt-1 font-bold text-navy">—</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Amount</span>
                            <p id="previewAmount" class="mt-1 font-black text-primary">—</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400">Status</span>
                            <p id="previewStatus" class="mt-1 inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-warning">—</p>
                        </div>
                    </div>
                </div>

                <div id="manualFieldsContainer" class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                            Cheque Number <span class="text-danger">*</span>
                        </label>
                        <input name="cheque_no" id="chequeNoInput" value="{{ old('cheque_no') }}" placeholder="Enter cheque number" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                            Bank Name <span class="text-danger">*</span>
                        </label>
                        <input name="bank_name" id="bankNameInput" value="{{ old('bank_name') }}" placeholder="Enter bank name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                            Cheque Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="cheque_date" id="chequeDateInput" value="{{ old('cheque_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                            Amount (Rs) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" value="{{ old('amount') }}" placeholder="Enter amount" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                </div>

                <div id="statusSelectContainer">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="statusSelect" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="{{ Cheque::STATUS_PENDING }}" @selected(old('status') === Cheque::STATUS_PENDING)>Pending</option>
                        <option value="{{ Cheque::STATUS_DEPOSITED }}" id="statusOptionDeposited" @selected(old('status') === Cheque::STATUS_DEPOSITED)>Deposited</option>
                        <option value="{{ Cheque::STATUS_PASSED }}" @selected(old('status') === Cheque::STATUS_PASSED)>Passed</option>
                        <option value="{{ Cheque::STATUS_RETURNED }}" @selected(old('status') === Cheque::STATUS_RETURNED)>Returned</option>
                        <option value="{{ Cheque::STATUS_CANCELLED }}" @selected(old('status') === Cheque::STATUS_CANCELLED)>Cancelled</option>
                        <option value="{{ Cheque::STATUS_HOLD }}" @selected(old('status') === Cheque::STATUS_HOLD)>Hold</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">Notes / Remarks</label>
                    <textarea name="notes" rows="3" placeholder="Enter notes about this cheque..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('notes') }}</textarea>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-navy">Attachment if available</label>
                    <input type="file" name="attachment" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-bold file:text-primary">
                </div>

                <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                    <a href="{{ route('cheques.index') }}" class="flex-1 rounded-2xl bg-slate-100 py-3 text-center text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                        Cancel
                    </a>
                    <button type="submit" class="flex flex-[2] items-center justify-center gap-2 rounded-2xl bg-primary py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                        <i class="fa-regular fa-calendar-check"></i>
                        Save Cheque
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        const chequeType = document.getElementById('chequeType');
        const customerSelectContainer = document.getElementById('customerSelectContainer');
        const supplierSelectContainer = document.getElementById('supplierSelectContainer');
        const supplierModeContainer = document.getElementById('supplierModeContainer');
        const sourceChequeContainer = document.getElementById('sourceChequeContainer');
        const sourceChequeIdInput = document.getElementById('sourceChequeIdInput');
        const combinedChequesContainer = document.getElementById('combinedChequesContainer');
        const combinedOwnChequeRows = document.getElementById('combinedOwnChequeRows');
        const combinedCustomerChequeList = document.getElementById('combinedCustomerChequeList');
        const addOwnChequeButton = document.getElementById('addOwnChequeButton');
        const addCustomerChequeButton = document.getElementById('addCustomerChequeButton');
        const manualFieldsContainer = document.getElementById('manualFieldsContainer');
        const transferInfoBox = document.getElementById('transferInfoBox');
        const transferPreviewCard = document.getElementById('transferPreviewCard');
        const statusOptionDeposited = document.getElementById('statusOptionDeposited');
        const chequeTypeHelp = document.getElementById('chequeTypeHelp');

        const chequeNoInput = document.getElementById('chequeNoInput');
        const bankNameInput = document.getElementById('bankNameInput');
        const chequeDateInput = document.getElementById('chequeDateInput');
        const amountInput = document.getElementById('amountInput');
        const statusSelect = document.getElementById('statusSelect');
        const detailFields = [chequeNoInput, bankNameInput, chequeDateInput, amountInput];
        let selectedSourceCheque = null;
        const combinedCustomerCheques = new Map();

        function createSearchSelect(selector, settings) {
            if (window.TomSelect) {
                return new TomSelect(selector, settings);
            }

            const input = document.querySelector(selector);
            if (!input) return null;

            // Hide native select
            input.style.display = 'none';

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select-wrapper relative w-full';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            // Create button
            const btn = document.createElement('div');
            btn.className = 'custom-btn w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-navy flex items-center justify-between cursor-pointer transition';
            btn.innerHTML = `<span class="current-value text-slate-400">${settings.placeholder || 'Select...'}</span><i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>`;
            wrapper.appendChild(btn);

            // Create dropdown menu
            const menu = document.createElement('div');
            menu.className = 'custom-menu absolute left-0 right-0 mt-1 z-[100] bg-white border border-slate-200 rounded-2xl shadow-xl p-2 hidden';
            
            // Search input
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Type to search...';
            searchInput.className = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 mb-2';
            menu.appendChild(searchInput);

            // Options container
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'max-h-60 overflow-y-auto space-y-1';
            menu.appendChild(optionsContainer);
            wrapper.appendChild(menu);

            const select = {
                native: false,
                input,
                wrapper,
                options: {},
                getValue() {
                    return input.value;
                },
                setValue(val, silent) {
                    if (val && !Array.from(input.options).some(option => option.value === String(val))) {
                        const option = document.createElement('option');
                        option.value = val;
                        option.textContent = select.options[val]?.text || select.options[String(val)]?.text || val;
                        input.appendChild(option);
                    }

                    input.value = val;
                    const selectedOpt = select.options[val] || select.options[String(val)];
                    if (selectedOpt) {
                        const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
                        const label = settings.render && settings.render.item ? settings.render.item(selectedOpt, escapeHtml) : (selectedOpt.text || selectedOpt.name);
                        btn.querySelector('.current-value').innerHTML = label;
                        btn.querySelector('.current-value').classList.remove('text-slate-400');
                    } else {
                        btn.querySelector('.current-value').innerHTML = settings.placeholder || 'Select...';
                        btn.querySelector('.current-value').classList.add('text-slate-400');
                    }
                    if (!silent) {
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                },
                clear(silent) {
                    select.setValue('', silent);
                },
                on(event, callback) {
                    input.addEventListener(event, () => callback(input.value));
                }
            };

            // Toggle dropdown
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other open custom dropdowns
                document.querySelectorAll('.custom-select-wrapper').forEach(el => {
                    const m = el.querySelector('.custom-menu');
                    const b = el.querySelector('.custom-btn');
                    if (m && m !== menu) {
                        m.classList.add('hidden');
                        b.classList.remove('border-primary', 'ring-4', 'ring-primary/10');
                    }
                });

                menu.classList.toggle('hidden');
                if (!menu.classList.contains('hidden')) {
                    btn.classList.add('border-primary', 'ring-4', 'ring-primary/10');
                    searchInput.focus();
                    if (settings.load && Object.keys(select.options).length === 0) {
                        triggerSearch('');
                    }
                } else {
                    btn.classList.remove('border-primary', 'ring-4', 'ring-primary/10');
                }
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    menu.classList.add('hidden');
                    btn.classList.remove('border-primary', 'ring-4', 'ring-primary/10');
                }
            });

            const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

            function renderOptions(items) {
                optionsContainer.innerHTML = '';
                if (items.length === 0) {
                    optionsContainer.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No results found</div>';
                    return;
                }

                items.forEach(item => {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'custom-select-option transition';
                    
                    const optionHtml = settings.render && settings.render.option 
                        ? settings.render.option(item, escapeHtml) 
                        : `<div class="font-semibold">${escapeHtml(item.text || item.name)}</div>`;
                    
                    optionDiv.innerHTML = optionHtml;

                    optionDiv.addEventListener('click', (e) => {
                        select.setValue(item.id);
                        menu.classList.add('hidden');
                        btn.classList.remove('border-primary', 'ring-4', 'ring-primary/10');
                    });

                    optionsContainer.appendChild(optionDiv);
                });
            }

            let searchTimeout = null;
            function triggerSearch(query) {
                if (settings.load) {
                    if (searchTimeout) clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        optionsContainer.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Searching...</div>';
                        settings.load(query, (results) => {
                            if (results) {
                                results.forEach(r => {
                                    select.options[r.id] = r;
                                });
                                renderOptions(results);
                            } else {
                                renderOptions([]);
                            }
                        });
                    }, 250);
                } else {
                    const filtered = Object.values(select.options).filter(opt => {
                        const searchFields = settings.searchField || ['text'];
                        return searchFields.some(field => {
                            const val = String(opt[field] || '').toLowerCase();
                            return val.includes(query.toLowerCase());
                        });
                    });
                    renderOptions(filtered);
                }
            }

            searchInput.addEventListener('input', (e) => {
                triggerSearch(searchInput.value);
            });

            // Populate initial static options
            Array.from(input.options).forEach(option => {
                if (!option.value) return;
                
                const optData = {
                    id: option.value,
                    text: option.textContent.trim(),
                    name: option.textContent.trim().split(' - ')[0],
                    business_name: option.getAttribute('data-business') || '',
                    phone: option.getAttribute('data-phone') || ''
                };
                select.options[option.value] = optData;
            });

            if (!settings.load) {
                renderOptions(Object.values(select.options));
            }

            // Set initial selected value if present on load
            if (input.value) {
                select.setValue(input.value, true);
            }

            return select;
        }

        const customerSelect = createSearchSelect('#customerSelect', {
            valueField: 'id',
            labelField: 'text',
            searchField: ['name', 'business_name', 'phone', 'text'],
            placeholder: 'Search and select customer',
            load(query, callback) {
                fetch('/ajax/customers/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
            render: {
                option(item, escape) {
                    return `<div><div class="font-bold text-navy">${escape(item.name || item.text)}</div><div class="text-xs text-slate-500">${escape(item.business_name || '')}${item.phone ? ' | ' + escape(item.phone) : ''}</div></div>`;
                },
                item(item, escape) {
                    return `<div>${escape(item.text)}</div>`;
                }
            }
        });

        const supplierSelect = createSearchSelect('#supplierSelect', {
            valueField: 'id',
            labelField: 'text',
            searchField: ['name', 'business_name', 'phone', 'text'],
            placeholder: 'Search and select supplier',
            load(query, callback) {
                fetch('/ajax/suppliers/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
            render: {
                option(item, escape) {
                    return `<div><div class="font-bold text-navy">${escape(item.name || item.text)}</div><div class="text-xs text-slate-500">${escape(item.business_name || '')}${item.phone ? ' | ' + escape(item.phone) : ''}</div></div>`;
                },
                item(item, escape) {
                    return `<div>${escape(item.text)}</div>`;
                }
            }
        });

        const sourceChequeSelect = createSearchSelect('#sourceChequeSelect', {
            valueField: 'id',
            labelField: 'text',
            searchField: ['cheque_no', 'customer_name', 'bank_name', 'amount', 'text'],
            placeholder: 'Search existing customer received cheque',
            load(query, callback) {
                fetch('/ajax/customer-cheques/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
            render: {
                option(item, escape) {
                    return `<div>
                        <div class="font-bold text-navy">${escape(item.cheque_no)} - ${escape(item.customer_name)}</div>
                        <div class="text-xs text-slate-500">${escape(item.bank_name)} | ${escape(item.formatted_amount || formatRs(item.amount))} | ${formatDate(item.cheque_date)}</div>
                    </div>`;
                },
                item(item, escape) {
                    return `<div>${escape(item.text)}</div>`;
                }
            }
        });

        const combinedSourceChequeSelect = createSearchSelect('#combinedSourceChequeSelect', {
            valueField: 'id',
            labelField: 'text',
            searchField: ['cheque_no', 'customer_name', 'bank_name', 'amount', 'text'],
            placeholder: 'Search existing customer received cheque',
            load(query, callback) {
                fetch('/ajax/customer-cheques/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
            render: {
                option(item, escape) {
                    return `<div>
                        <div class="font-bold text-navy">${escape(item.cheque_no)} - ${escape(item.customer_name)}</div>
                        <div class="text-xs text-slate-500">${escape(item.bank_name)} | ${escape(item.formatted_amount || formatRs(item.amount))} | ${formatDate(item.cheque_date)}</div>
                    </div>`;
                },
                item(item, escape) {
                    return `<div>${escape(item.text)}</div>`;
                }
            }
        });

        async function loadNativeSourceChequeOptions() {
            if (!sourceChequeSelect.native) {
                return;
            }

            const response = await fetch('/ajax/customer-cheques/search?q=');
            const cheques = await response.json();
            sourceChequeSelect.input.innerHTML = '<option value="">Search existing customer received cheque</option>';
            sourceChequeSelect.options = {};

            cheques.forEach(cheque => {
                const option = document.createElement('option');
                option.value = cheque.id;
                option.textContent = cheque.text;
                sourceChequeSelect.input.appendChild(option);
                sourceChequeSelect.options[cheque.id] = cheque;
            });
        }

        if (sourceChequeSelect.native) {
            sourceChequeSelect.input.addEventListener('focus', loadNativeSourceChequeOptions);
        }

        function selectedSupplierMode() {
            return document.querySelector('input[name="supplier_cheque_mode"]:checked')?.value || 'own_cheque';
        }

        function setRequired(select, required) {
            select.input.required = false;
            select.wrapper.classList.toggle('required', required);
        }

        function setDetailFieldsReadonly(readonly) {
            detailFields.forEach(field => {
                field.readOnly = readonly;
                field.classList.toggle('bg-slate-100', readonly);
            });
        }

        function setManualRequired(required) {
            detailFields.forEach(field => field.required = required);
            statusSelect.required = required;
        }

        function clearSourceCheque() {
            selectedSourceCheque = null;
            sourceChequeIdInput.value = '';
            if (sourceChequeSelect.getValue()) {
                sourceChequeSelect.clear(true);
            }
            chequeNoInput.value = '';
            bankNameInput.value = '';
            chequeDateInput.value = '';
            amountInput.value = '';
            statusSelect.value = 'pending';
            transferInfoBox.classList.add('hidden');
            transferPreviewCard.classList.add('hidden');
        }

        function syncForm() {
            const type = chequeType.value;
            const mode = selectedSupplierMode();
            const isCustomerReceived = type === 'customer_received';
            const isSupplierIssued = type === 'own_issued';
            const isUsingCustomerCheque = isSupplierIssued && mode === 'received_customer_cheque';
            const isCombined = isSupplierIssued && mode === 'combined_cheques';
            const shouldShowManualFields = isCustomerReceived || (isSupplierIssued && mode === 'own_cheque') || (isUsingCustomerCheque && selectedSourceCheque);

            customerSelectContainer.classList.toggle('hidden', !isCustomerReceived);
            supplierSelectContainer.classList.toggle('hidden', !isSupplierIssued);
            supplierModeContainer.classList.toggle('hidden', !isSupplierIssued);
            sourceChequeContainer.classList.toggle('hidden', !isUsingCustomerCheque);
            combinedChequesContainer.classList.toggle('hidden', !isCombined);
            manualFieldsContainer.classList.toggle('hidden', !shouldShowManualFields);
            document.getElementById('statusSelectContainer').classList.toggle('hidden', !shouldShowManualFields);

            setRequired(customerSelect, isCustomerReceived);
            setRequired(supplierSelect, isSupplierIssued);
            setRequired(sourceChequeSelect, isUsingCustomerCheque);
            setRequired(combinedSourceChequeSelect, false);
            setManualRequired(!isUsingCustomerCheque && !isCombined || Boolean(selectedSourceCheque));
            setDetailFieldsReadonly(isUsingCustomerCheque);

            statusOptionDeposited.hidden = !isCustomerReceived;
            statusOptionDeposited.disabled = !isCustomerReceived;
            if (!isCustomerReceived && statusSelect.value === 'deposited') {
                statusSelect.value = 'pending';
            }

            chequeTypeHelp.textContent = isCustomerReceived
                ? 'Record a cheque received from a customer.'
                : 'Issue a cheque to a supplier using your own cheque, customer received cheque, or a combined set.';

            if (!isUsingCustomerCheque) {
                transferInfoBox.classList.add('hidden');
                transferPreviewCard.classList.add('hidden');
            } else if (sourceChequeSelect.native && !selectedSourceCheque && sourceChequeSelect.input.options.length <= 1) {
                loadNativeSourceChequeOptions().catch(() => {});
            }
        }

        window.syncChequeForm = syncForm;

        function updateTransferInfo() {
            if (!selectedSourceCheque) {
                transferInfoBox.classList.add('hidden');
                return;
            }

            const supplierOption = supplierSelect.options[supplierSelect.getValue()];
            const supplierName = supplierOption ? (supplierOption.name || supplierOption.text.split(' - ')[0]) : 'selected supplier';

            document.getElementById('infoBoxCustomer').textContent = selectedSourceCheque.customer_name || 'the original customer';
            document.getElementById('infoBoxSupplier').textContent = supplierName;
            transferInfoBox.classList.remove('hidden');
        }

        function applySourceCheque(data) {
            selectedSourceCheque = data;
            sourceChequeIdInput.value = data.id || sourceChequeSelect.getValue() || '';
            chequeNoInput.value = data.cheque_no || '';
            bankNameInput.value = data.bank_name || '';
            chequeDateInput.value = data.cheque_date || '';
            amountInput.value = data.amount || '';
            statusSelect.value = 'pending';

            document.getElementById('previewCustomer').textContent = data.customer_name || '—';
            document.getElementById('previewChequeNo').textContent = data.cheque_no || '—';
            document.getElementById('previewBank').textContent = data.bank_name || '—';
            document.getElementById('previewDate').textContent = formatDate(data.cheque_date);
            document.getElementById('previewAmount').textContent = data.formatted_amount || formatRs(data.amount);
            document.getElementById('previewStatus').textContent = titleCase(data.status || 'pending');

            transferPreviewCard.classList.remove('hidden');
            updateTransferInfo();
            syncForm();
        }

        function addOwnChequeRow() {
            const key = Date.now().toString() + Math.floor(Math.random() * 1000);
            const row = document.createElement('div');
            row.className = 'rounded-2xl border border-slate-200 bg-white p-4';
            row.innerHTML = `
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h5 class="text-sm font-extrabold text-navy">Own Cheque</h5>
                    <button type="button" class="remove-own-cheque rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-100">
                        Remove
                    </button>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <input name="combined_own_cheques[${key}][cheque_no]" placeholder="Cheque number" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <input name="combined_own_cheques[${key}][bank_name]" placeholder="Bank name" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <input type="date" name="combined_own_cheques[${key}][cheque_date]" value="{{ now()->toDateString() }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <input type="number" step="0.01" min="0.01" name="combined_own_cheques[${key}][amount]" placeholder="Amount (Rs)" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <select name="combined_own_cheques[${key}][status]" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="pending">Pending</option>
                        <option value="passed">Passed</option>
                        <option value="returned">Returned</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="hold">Hold</option>
                    </select>
                    <input name="combined_own_cheques[${key}][notes]" placeholder="Notes" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
            `;

            row.querySelector('.remove-own-cheque').addEventListener('click', () => row.remove());
            combinedOwnChequeRows.appendChild(row);
        }

        function renderCombinedCustomerCheques() {
            combinedCustomerChequeList.innerHTML = '';

            if (combinedCustomerCheques.size === 0) {
                combinedCustomerChequeList.innerHTML = '<p class="text-xs font-semibold text-slate-400">No customer cheques added yet.</p>';
                return;
            }

            combinedCustomerCheques.forEach(cheque => {
                const row = document.createElement('div');
                row.className = 'rounded-2xl border border-teal/20 bg-teal/5 p-4';
                row.innerHTML = `
                    <input type="hidden" name="combined_source_customer_cheque_ids[]" value="${cheque.id}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-extrabold text-navy">${cheque.cheque_no} - ${cheque.customer_name}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">${cheque.bank_name} | ${cheque.formatted_amount || formatRs(cheque.amount)} | ${formatDate(cheque.cheque_date)}</p>
                        </div>
                        <button type="button" data-cheque-id="${cheque.id}" class="remove-customer-cheque rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-100">
                            Remove
                        </button>
                    </div>
                `;
                row.querySelector('.remove-customer-cheque').addEventListener('click', event => {
                    combinedCustomerCheques.delete(event.currentTarget.dataset.chequeId);
                    renderCombinedCustomerCheques();
                });
                combinedCustomerChequeList.appendChild(row);
            });
        }

        function addCombinedCustomerCheque() {
            const value = combinedSourceChequeSelect.getValue();
            if (!value) {
                alert('Please select a customer received cheque.');
                return;
            }

            const selected = combinedSourceChequeSelect.options[value];
            if (!selected) {
                alert('Could not read the selected customer cheque.');
                return;
            }

            combinedCustomerCheques.set(String(value), selected);
            combinedSourceChequeSelect.clear(true);
            renderCombinedCustomerCheques();
        }

        function formatRs(amount) {
            return 'Rs ' + Number(amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDate(dateValue) {
            if (!dateValue) {
                return '—';
            }

            return new Date(dateValue + 'T00:00:00').toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function titleCase(value) {
            return String(value).replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
        }

        chequeType.addEventListener('change', () => {
            if (chequeType.value === 'customer_received') {
                supplierSelect.clear(true);
                clearSourceCheque();
            } else {
                customerSelect.clear(true);
            }
            syncForm();
        });

        document.querySelectorAll('input[name="supplier_cheque_mode"]').forEach(input => {
            input.addEventListener('change', () => {
                clearSourceCheque();
                syncForm();
            });
        });

        supplierSelect.on('change', updateTransferInfo);
        addOwnChequeButton.addEventListener('click', addOwnChequeRow);
        addCustomerChequeButton.addEventListener('click', addCombinedCustomerCheque);
        renderCombinedCustomerCheques();

        sourceChequeSelect.on('change', value => {
            if (!value) {
                clearSourceCheque();
                syncForm();
                return;
            }

            fetch('/ajax/customer-cheques/' + value)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        clearSourceCheque();
                        alert(data.error);
                        return;
                    }
                    applySourceCheque(data);
                })
                .catch(() => {
                    clearSourceCheque();
                    alert('Could not load the selected cheque details.');
                });
        });

        // Custom validation on submit to prevent HTML5 hidden focusable errors
        document.querySelector('form').addEventListener('submit', (e) => {
            const type = chequeType.value;
            const mode = selectedSupplierMode();

            if (type === 'customer_received') {
                if (!customerSelect.getValue()) {
                    e.preventDefault();
                    alert('Please select a customer.');
                    if (customerSelect.wrapper.querySelector('.custom-btn')) {
                        customerSelect.wrapper.querySelector('.custom-btn').click();
                    } else if (customerSelect.focus) {
                        customerSelect.focus();
                    }
                    return;
                }
            } else if (type === 'own_issued') {
                if (!supplierSelect.getValue()) {
                    e.preventDefault();
                    alert('Please select a supplier.');
                    if (supplierSelect.wrapper.querySelector('.custom-btn')) {
                        supplierSelect.wrapper.querySelector('.custom-btn').click();
                    } else if (supplierSelect.focus) {
                        supplierSelect.focus();
                    }
                    return;
                }

                if (mode === 'received_customer_cheque') {
                    sourceChequeIdInput.value = sourceChequeSelect.getValue() || sourceChequeIdInput.value;

                    if (!sourceChequeIdInput.value) {
                        e.preventDefault();
                        alert('Please select a customer received cheque.');
                        if (sourceChequeSelect.wrapper.querySelector('.custom-btn')) {
                            sourceChequeSelect.wrapper.querySelector('.custom-btn').click();
                        } else if (sourceChequeSelect.focus) {
                            sourceChequeSelect.focus();
                        }
                        return;
                    }
                }

                if (mode === 'combined_cheques') {
                    const hasCustomerCheques = combinedCustomerCheques.size > 0;
                    const hasOwnChequeRows = Array.from(combinedOwnChequeRows.querySelectorAll('input[name$="[cheque_no]"]'))
                        .some(input => input.value.trim() !== '');

                    if (!hasCustomerCheques && !hasOwnChequeRows) {
                        e.preventDefault();
                        alert('Add at least one own cheque or customer received cheque.');
                        return;
                    }
                }
            }
        });

        syncForm();
    </script>
@endpush
