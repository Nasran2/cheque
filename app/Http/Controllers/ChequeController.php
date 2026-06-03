<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\ChequeAuditLog;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\ChequeService;
use App\Support\Currency;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChequeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Cheque::with(['customer', 'supplier', 'originalCustomer', 'givenToSupplier'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('cheque_no', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('cheque_type'), fn ($query) => $query->where('cheque_type', $request->cheque_type))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('bank'), fn ($query) => $query->where('bank_name', $request->bank))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('cheque_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('cheque_date', '<=', $request->to_date));

        $summaryQuery = clone $query;

        /** @var LengthAwarePaginator $cheques */
        $cheques = $query
            ->latest('cheque_date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total_count' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('amount'),
            'customer_amount' => (clone $summaryQuery)->type(Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
            'own_amount' => (clone $summaryQuery)->type(Cheque::TYPE_OWN_ISSUED)->sum('amount'),
        ];

        $banks = Cheque::whereNotNull('bank_name')
            ->distinct()
            ->orderBy('bank_name')
            ->pluck('bank_name');

        return view('cheques.index', compact('cheques', 'summary', 'banks'));
    }

    public function create(Request $request): View
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('cheques.create', compact('customers', 'suppliers'));
    }

    public function pending(Request $request): View
    {
        return $this->statusPage($request, Cheque::STATUS_PENDING, 'cheques.pending');
    }

    public function passed(Request $request): View
    {
        return $this->statusPage($request, Cheque::STATUS_PASSED, 'cheques.passed');
    }

    public function returned(Request $request): View
    {
        return $this->statusPage($request, Cheque::STATUS_RETURNED, 'cheques.returned');
    }

    public function upcoming(Request $request): View
    {
        $month = Carbon::parse($request->input('month', today()->format('Y-m')) . '-01');
        $selectedDate = Carbon::parse($request->input('date', today()->toDateString()));
        $includedStatuses = [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD];

        $upcomingQuery = Cheque::with(['customer', 'supplier'])
            ->whereIn('status', $includedStatuses)
            ->whereDate('cheque_date', '>=', today());

        $summary = [
            'today_count' => (clone $upcomingQuery)->whereDate('cheque_date', today())->count(),
            'tomorrow_count' => (clone $upcomingQuery)->whereDate('cheque_date', today()->addDay())->count(),
            'next_7_count' => (clone $upcomingQuery)->whereBetween('cheque_date', [today(), today()->addDays(7)])->count(),
            'next_30_count' => (clone $upcomingQuery)->whereBetween('cheque_date', [today(), today()->addDays(30)])->count(),
            'customer_amount' => (clone $upcomingQuery)->type(Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
            'own_amount' => (clone $upcomingQuery)->type(Cheque::TYPE_OWN_ISSUED)->sum('amount'),
            'total_amount' => (clone $upcomingQuery)->sum('amount'),
            'overdue_amount' => Cheque::whereIn('status', $includedStatuses)->whereDate('cheque_date', '<', today())->sum('amount'),
        ];

        $monthCheques = Cheque::with(['customer', 'supplier'])
            ->whereIn('status', $includedStatuses)
            ->whereBetween('cheque_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->groupBy(fn (Cheque $cheque) => $cheque->cheque_date->toDateString());

        $selectedCheques = Cheque::with(['customer', 'supplier'])
            ->whereIn('status', $includedStatuses)
            ->whereDate('cheque_date', $selectedDate)
            ->latest()
            ->get();

        $calendarStart = $month->copy()->startOfMonth()->startOfWeek();
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek();
        $calendarDays = collect(CarbonPeriod::create($calendarStart, $calendarEnd));
        $dateChips = collect(CarbonPeriod::create(today(), today()->addDays(13)));

        return view('cheques.upcoming', compact(
            'summary',
            'month',
            'selectedDate',
            'monthCheques',
            'selectedCheques',
            'calendarDays',
            'dateChips'
        ));
    }

    public function store(Request $request, ChequeService $chequeService): RedirectResponse
    {
        $chequeType = $request->input('cheque_type');
        $supplierMode = $request->input('supplier_cheque_mode', 'own_cheque');

        $baseRules = [
            'cheque_type' => ['required', Rule::in([Cheque::TYPE_CUSTOMER_RECEIVED, Cheque::TYPE_OWN_ISSUED])],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];

        if ($chequeType === Cheque::TYPE_CUSTOMER_RECEIVED) {
            $validated = $request->validate($baseRules + [
                'customer_id' => ['required', 'exists:customers,id'],
                'cheque_no' => ['required', 'string', 'max:50'],
                'bank_name' => ['required', 'string', 'max:120'],
                'cheque_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:1'],
                'status' => ['required', Rule::in([
                    Cheque::STATUS_PENDING,
                    Cheque::STATUS_DEPOSITED,
                    Cheque::STATUS_PASSED,
                    Cheque::STATUS_RETURNED,
                    Cheque::STATUS_CANCELLED,
                    Cheque::STATUS_HOLD,
                ])],
            ]);

            $this->ensureChequeNumberIsAvailable($validated['cheque_no'], $validated['bank_name']);

            $chequeService->create([
                'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
                'customer_id' => $validated['customer_id'],
                'supplier_id' => null,
                'supplier_cheque_mode' => null,
                'source_customer_cheque_id' => null,
                'original_customer_id' => null,
                'given_to_supplier_id' => null,
                'cheque_no' => $validated['cheque_no'],
                'bank_name' => $validated['bank_name'],
                'account_no' => null,
                'cheque_date' => $validated['cheque_date'],
                'received_or_issued_date' => now()->toDateString(),
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ], $request->user(), $request->file('attachment'));
        } elseif ($chequeType === Cheque::TYPE_OWN_ISSUED && $supplierMode === 'received_customer_cheque') {
            $validated = $request->validate($baseRules + [
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'supplier_cheque_mode' => ['required', Rule::in(['received_customer_cheque'])],
                'source_customer_cheque_id' => ['required', 'exists:cheques,id'],
                'status' => ['nullable', Rule::in([
                    Cheque::STATUS_PENDING,
                    Cheque::STATUS_PASSED,
                    Cheque::STATUS_RETURNED,
                    Cheque::STATUS_CANCELLED,
                    Cheque::STATUS_HOLD,
                ])],
            ]);

            DB::transaction(function () use ($validated, $request, $chequeService): void {
                $sourceCheque = Cheque::with('customer')
                    ->whereKey($validated['source_customer_cheque_id'])
                    ->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)
                    ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sourceCheque->is_transferred_to_supplier) {
                    throw ValidationException::withMessages([
                        'source_customer_cheque_id' => 'This customer cheque has already been given to a supplier.',
                    ]);
                }

                $this->createSupplierChequeFromCustomerCheque(
                    $sourceCheque,
                    Supplier::findOrFail($validated['supplier_id']),
                    $validated['notes'] ?? null,
                    $validated['status'] ?? Cheque::STATUS_PENDING,
                    $request,
                    $chequeService,
                    $request->file('attachment')
                );
            });
        } elseif ($chequeType === Cheque::TYPE_OWN_ISSUED && $supplierMode === 'combined_cheques') {
            $validated = $request->validate($baseRules + [
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'supplier_cheque_mode' => ['required', Rule::in(['combined_cheques'])],
                'combined_source_customer_cheque_ids' => ['nullable', 'array'],
                'combined_source_customer_cheque_ids.*' => ['integer', 'exists:cheques,id', 'distinct'],
                'combined_own_cheques' => ['nullable', 'array'],
                'combined_own_cheques.*.cheque_no' => ['nullable', 'string', 'max:50'],
                'combined_own_cheques.*.bank_name' => ['nullable', 'string', 'max:120'],
                'combined_own_cheques.*.cheque_date' => ['nullable', 'date'],
                'combined_own_cheques.*.amount' => ['nullable', 'numeric', 'min:1'],
                'combined_own_cheques.*.status' => ['nullable', Rule::in([
                    Cheque::STATUS_PENDING,
                    Cheque::STATUS_PASSED,
                    Cheque::STATUS_RETURNED,
                    Cheque::STATUS_CANCELLED,
                    Cheque::STATUS_HOLD,
                ])],
                'combined_own_cheques.*.notes' => ['nullable', 'string'],
            ]);

            $ownCheques = collect($validated['combined_own_cheques'] ?? [])
                ->filter(fn (array $cheque) => filled($cheque['cheque_no'] ?? null) || filled($cheque['bank_name'] ?? null) || filled($cheque['amount'] ?? null))
                ->values();
            $sourceIds = collect($validated['combined_source_customer_cheque_ids'] ?? [])->filter()->unique()->values();

            if ($ownCheques->isEmpty() && $sourceIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'supplier_cheque_mode' => 'Add at least one own cheque or customer received cheque.',
                ]);
            }

            foreach ($ownCheques as $index => $cheque) {
                foreach (['cheque_no', 'bank_name', 'cheque_date', 'amount'] as $field) {
                    if (! filled($cheque[$field] ?? null)) {
                        throw ValidationException::withMessages([
                            "combined_own_cheques.{$index}.{$field}" => 'Complete all own cheque fields or remove this row.',
                        ]);
                    }
                }

                $this->ensureChequeNumberIsAvailable($cheque['cheque_no'], $cheque['bank_name']);
            }

            DB::transaction(function () use ($ownCheques, $sourceIds, $validated, $request, $chequeService): void {
                $supplier = Supplier::findOrFail($validated['supplier_id']);

                foreach ($ownCheques as $cheque) {
                    $chequeService->create([
                        'cheque_type' => Cheque::TYPE_OWN_ISSUED,
                        'supplier_cheque_mode' => 'own_cheque',
                        'supplier_id' => $supplier->id,
                        'customer_id' => null,
                        'source_customer_cheque_id' => null,
                        'original_customer_id' => null,
                        'given_to_supplier_id' => null,
                        'cheque_no' => $cheque['cheque_no'],
                        'bank_name' => $cheque['bank_name'],
                        'account_no' => null,
                        'cheque_date' => $cheque['cheque_date'],
                        'received_or_issued_date' => now()->toDateString(),
                        'amount' => $cheque['amount'],
                        'status' => $cheque['status'] ?? Cheque::STATUS_PENDING,
                        'notes' => $cheque['notes'] ?? "Own cheque issued to supplier {$supplier->name}.",
                    ], $request->user());
                }

                foreach ($sourceIds as $sourceId) {
                    $sourceCheque = Cheque::with('customer')
                        ->whereKey($sourceId)
                        ->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)
                        ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($sourceCheque->is_transferred_to_supplier) {
                        throw ValidationException::withMessages([
                            'combined_source_customer_cheque_ids' => "Customer cheque {$sourceCheque->cheque_no} has already been given to a supplier.",
                        ]);
                    }

                    $this->createSupplierChequeFromCustomerCheque(
                        $sourceCheque,
                        $supplier,
                        $validated['notes'] ?? null,
                        Cheque::STATUS_PENDING,
                        $request,
                        $chequeService
                    );
                }
            });
        } else {
            $validated = $request->validate($baseRules + [
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'supplier_cheque_mode' => ['required', Rule::in(['own_cheque'])],
                'cheque_no' => ['required', 'string', 'max:50'],
                'bank_name' => ['required', 'string', 'max:120'],
                'cheque_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:1'],
                'status' => ['required', Rule::in([
                    Cheque::STATUS_PENDING,
                    Cheque::STATUS_PASSED,
                    Cheque::STATUS_RETURNED,
                    Cheque::STATUS_CANCELLED,
                    Cheque::STATUS_HOLD,
                ])],
            ]);

            $this->ensureChequeNumberIsAvailable($validated['cheque_no'], $validated['bank_name']);
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $notes = $validated['notes'] ?? "Own cheque issued to supplier {$supplier->name}.";

            $chequeService->create([
                'cheque_type' => Cheque::TYPE_OWN_ISSUED,
                'supplier_cheque_mode' => 'own_cheque',
                'supplier_id' => $supplier->id,
                'customer_id' => null,
                'source_customer_cheque_id' => null,
                'original_customer_id' => null,
                'given_to_supplier_id' => null,
                'cheque_no' => $validated['cheque_no'],
                'bank_name' => $validated['bank_name'],
                'account_no' => null,
                'cheque_date' => $validated['cheque_date'],
                'received_or_issued_date' => now()->toDateString(),
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'notes' => $notes,
            ], $request->user(), $request->file('attachment'));
        }

        return redirect()->route('cheques.index')->with('success', 'Cheque saved successfully.');
    }

    public function show(Cheque $cheque): View
    {
        $cheque->load(['customer', 'supplier', 'sourceCustomerCheque.customer', 'originalCustomer', 'givenToSupplier']);
        
        $transferredToCheque = null;
        if ($cheque->is_transferred_to_supplier) {
            $transferredToCheque = Cheque::where('source_customer_cheque_id', $cheque->id)->first();
        }

        return view('cheques.show', compact('cheque', 'transferredToCheque'));
    }

    public function markPassed(Request $request, Cheque $cheque, ChequeService $chequeService): RedirectResponse
    {
        $this->ensureCanChangeStatus($cheque, Cheque::STATUS_PASSED);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $chequeService->changeStatus($cheque, Cheque::STATUS_PASSED, [
            'date' => $validated['date'] ?? now()->toDateString(),
            'note' => $validated['note'] ?? 'Marked as passed from cheque list.',
        ], $request->user());

        return back()->with('success', "Cheque {$cheque->cheque_no} marked as passed.");
    }

    public function markReturned(Request $request, Cheque $cheque, ChequeService $chequeService): RedirectResponse
    {
        $this->ensureCanChangeStatus($cheque, Cheque::STATUS_RETURNED);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'returned_reason' => ['nullable', 'string', 'max:255'],
            'return_charge' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $chequeService->changeStatus($cheque, Cheque::STATUS_RETURNED, [
            'date' => $validated['date'] ?? now()->toDateString(),
            'returned_reason' => $validated['returned_reason'] ?? 'Marked returned from cheque list.',
            'return_charge' => $validated['return_charge'] ?? $cheque->return_charge,
            'note' => $validated['note'] ?? 'Marked as returned from cheque list.',
        ], $request->user());

        return back()->with('success', "Cheque {$cheque->cheque_no} marked as returned.");
    }

    public function searchCustomers(Request $request)
    {
        $q = $request->input('q', '');
        $customers = Customer::where('status', 'active')
            ->when(!empty($q), function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('business_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->limit(30)
            ->get()
            ->map(function ($c) {
                $business = $c->business_name ? " - {$c->business_name}" : "";
                $phone = $c->phone ? " ({$c->phone})" : "";
                return [
                    'id' => $c->id,
                    'text' => $c->name . $business . $phone,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'business_name' => $c->business_name,
                ];
            });

        return response()->json($customers);
    }

    public function searchSuppliers(Request $request)
    {
        $q = $request->input('q', '');
        $suppliers = Supplier::where('status', 'active')
            ->when(!empty($q), function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('business_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->limit(30)
            ->get()
            ->map(function ($s) {
                $business = $s->business_name ? " - {$s->business_name}" : "";
                $phone = $s->phone ? " ({$s->phone})" : "";
                return [
                    'id' => $s->id,
                    'text' => $s->name . $business . $phone,
                    'name' => $s->name,
                    'phone' => $s->phone,
                    'business_name' => $s->business_name,
                ];
            });

        return response()->json($suppliers);
    }

    public function searchCustomerCheques(Request $request)
    {
        $q = $request->input('q', '');
        $cheques = Cheque::with('customer')
            ->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)
            ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
            ->where('is_transferred_to_supplier', false)
            ->when(!empty($q), function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('cheque_no', 'like', "%{$q}%")
                        ->orWhere('bank_name', 'like', "%{$q}%")
                        ->orWhere('amount', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$q}%"));
                });
            })
            ->limit(30)
            ->get()
            ->map(function ($ch) {
                $customerName = $ch->customer?->name ?? 'No customer';
                $amountLkr = \App\Support\Currency::formatLkr($ch->amount);
                $dateFormatted = $ch->cheque_date?->format('d M Y') ?? '';
                $text = "{$ch->cheque_no} - {$customerName} - {$ch->bank_name} - {$amountLkr} - {$dateFormatted}";
                return [
                    'id' => $ch->id,
                    'text' => $text,
                    'cheque_no' => $ch->cheque_no,
                    'customer_name' => $customerName,
                    'bank_name' => $ch->bank_name,
                    'cheque_date' => $ch->cheque_date?->toDateString(),
                    'amount' => $ch->amount,
                    'formatted_amount' => $amountLkr,
                    'status' => $ch->status,
                ];
            });

        return response()->json($cheques);
    }

    public function customerChequeDetails(Cheque $cheque)
    {
        if (
            $cheque->cheque_type !== Cheque::TYPE_CUSTOMER_RECEIVED
            || $cheque->is_transferred_to_supplier
            || ! in_array($cheque->status, [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD], true)
        ) {
            return response()->json(['error' => 'Not a customer cheque'], 400);
        }

        $cheque->load('customer');

        return response()->json([
            'id' => $cheque->id,
            'cheque_no' => $cheque->cheque_no,
            'customer_id' => $cheque->customer_id,
            'customer_name' => $cheque->customer?->name ?? 'No customer',
            'bank_name' => $cheque->bank_name,
            'cheque_date' => $cheque->cheque_date?->toDateString(),
            'amount' => $cheque->amount,
            'formatted_amount' => Currency::formatLkr($cheque->amount),
            'status' => $cheque->status,
            'notes' => $cheque->notes,
        ]);
    }

    private function ensureChequeNumberIsAvailable(string $chequeNo, string $bankName): void
    {
        $exists = Cheque::where('cheque_no', $chequeNo)
            ->where('bank_name', $bankName)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'cheque_no' => 'Cheque number already exists for this bank.',
            ]);
        }
    }

    private function ensureCanChangeStatus(Cheque $cheque, string $newStatus): void
    {
        if ($cheque->status === $newStatus) {
            throw ValidationException::withMessages([
                'status' => "Cheque is already {$newStatus}.",
            ]);
        }

        if (in_array($cheque->status, [Cheque::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Cancelled cheques cannot be updated from this page.',
            ]);
        }
    }

    private function createSupplierChequeFromCustomerCheque(
        Cheque $sourceCheque,
        Supplier $supplier,
        ?string $userNotes,
        string $status,
        Request $request,
        ChequeService $chequeService,
        mixed $attachment = null
    ): Cheque {
        $customerName = $sourceCheque->customer?->name ?? 'Unknown Customer';
        $supplierName = $supplier->name;
        $dateToday = now()->format('d M Y');
        $transferNote = "Customer cheque {$sourceCheque->cheque_no} received from {$customerName} was given to supplier {$supplierName} on {$dateToday}.";
        $supplierNote = "This supplier cheque was created using a customer received cheque. Original customer: {$customerName}. Cheque No: {$sourceCheque->cheque_no}. Bank: {$sourceCheque->bank_name}. Amount: " . Currency::formatLkr($sourceCheque->amount) . ". Given to supplier: {$supplierName} on {$dateToday}.";

        if (filled($userNotes)) {
            $supplierNote .= "\n" . $userNotes;
        }

        $newCheque = $chequeService->create([
            'cheque_type' => Cheque::TYPE_OWN_ISSUED,
            'supplier_cheque_mode' => 'received_customer_cheque',
            'supplier_id' => $supplier->id,
            'customer_id' => $sourceCheque->customer_id,
            'original_customer_id' => $sourceCheque->customer_id,
            'source_customer_cheque_id' => $sourceCheque->id,
            'given_to_supplier_id' => $supplier->id,
            'cheque_no' => $sourceCheque->cheque_no,
            'bank_name' => $sourceCheque->bank_name,
            'branch_name' => $sourceCheque->branch_name,
            'account_no' => null,
            'cheque_date' => $sourceCheque->cheque_date?->toDateString(),
            'received_or_issued_date' => now()->toDateString(),
            'amount' => $sourceCheque->amount,
            'status' => $status,
            'notes' => $supplierNote,
            'transfer_note' => $transferNote,
            'transferred_date' => now()->toDateString(),
        ], $request->user(), $attachment);

        $sourceCheque->forceFill([
            'is_transferred_to_supplier' => true,
            'given_to_supplier_id' => $supplier->id,
            'transferred_date' => now()->toDateString(),
            'transfer_note' => $transferNote,
            'notes' => trim(($sourceCheque->notes ? $sourceCheque->notes . "\n" : '') . "Cheque received from {$customerName} was given to supplier {$supplierName} on {$dateToday}. Supplier cheque record ID: {$newCheque->id}."),
            'updated_by' => $request->user()?->id,
        ])->save();

        ChequeAuditLog::create([
            'cheque_id' => $newCheque->id,
            'user_id' => $request->user()?->id,
            'action' => 'customer_cheque_given_to_supplier',
            'old_values' => [],
            'new_values' => [
                'original_cheque_id' => $sourceCheque->id,
                'new_cheque_id' => $newCheque->id,
                'customer_id' => $sourceCheque->customer_id,
                'supplier_id' => $supplier->id,
                'amount' => $newCheque->amount,
            ],
            'ip_address' => $request->ip(),
            'device' => $request->userAgent(),
            'note' => "Customer cheque {$sourceCheque->cheque_no} received from {$customerName} was given to supplier {$supplierName}.",
        ]);

        return $newCheque;
    }

    private function statusPage(Request $request, string $status, string $view): View
    {
        $query = $this->filteredStatusQuery($request, $status);
        $summaryQuery = clone $query;

        /** @var LengthAwarePaginator $cheques */
        $cheques = $query
            ->latest('cheque_date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total_count' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('amount'),
            'customer_amount' => (clone $summaryQuery)->type(Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
            'own_amount' => (clone $summaryQuery)->type(Cheque::TYPE_OWN_ISSUED)->sum('amount'),
        ];

        return view($view, [
            'cheques' => $cheques,
            'summary' => $summary,
            'banks' => $this->banksForStatus($status),
            'status' => $status,
        ]);
    }

    private function filteredStatusQuery(Request $request, string $status)
    {
        return Cheque::with(['customer', 'supplier', 'originalCustomer', 'givenToSupplier'])
            ->where('status', $status)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('cheque_no', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('cheque_type'), fn ($query) => $query->where('cheque_type', $request->cheque_type))
            ->when($request->filled('bank'), fn ($query) => $query->where('bank_name', $request->bank))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('cheque_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('cheque_date', '<=', $request->to_date));
    }

    private function banksForStatus(string $status): Collection
    {
        return Cheque::where('status', $status)
            ->whereNotNull('bank_name')
            ->distinct()
            ->orderBy('bank_name')
            ->pluck('bank_name');
    }
}
