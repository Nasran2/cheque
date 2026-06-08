<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\ChequeSetting;
use App\Support\ChequePresentation;
use App\Support\Currency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    // ── Report Registry ───────────────────────────────────────────────────────

    /**
     * The 7 active reports — each has a unique config used by both the view and PDF export.
     */
    private function reports(): array
    {
        return [
            'all-cheques' => [
                'title'       => 'All Cheques Report',
                'description' => 'Complete list of all cheques across every status.',
                'icon'        => 'fa-solid fa-list',
                'color'       => 'primary',
                'status'      => null,
                'upcoming'    => false,
                'route'       => 'reports.all-cheques',
            ],
            'pending-cheques' => [
                'title'       => 'Pending Cheques Report',
                'description' => 'Cheques awaiting deposit, clearance, or action.',
                'icon'        => 'fa-regular fa-clock',
                'color'       => 'warning',
                'status'      => Cheque::STATUS_PENDING,
                'upcoming'    => false,
                'route'       => 'reports.pending-cheques',
            ],
            'passed-cheques' => [
                'title'       => 'Passed Cheques Report',
                'description' => 'Cheques that have been successfully cleared.',
                'icon'        => 'fa-regular fa-circle-check',
                'color'       => 'success',
                'status'      => Cheque::STATUS_PASSED,
                'upcoming'    => false,
                'route'       => 'reports.passed-cheques',
            ],
            'returned-cheques' => [
                'title'       => 'Returned Cheques Report',
                'description' => 'Cheques that were returned or bounced.',
                'icon'        => 'fa-solid fa-rotate-left',
                'color'       => 'danger',
                'status'      => Cheque::STATUS_RETURNED,
                'upcoming'    => false,
                'route'       => 'reports.returned-cheques',
            ],
            'upcoming-cheques' => [
                'title'       => 'Upcoming Cheques Report',
                'description' => 'Active cheques with future due dates.',
                'icon'        => 'fa-regular fa-calendar-days',
                'color'       => 'teal',
                'status'      => null,
                'upcoming'    => true,
                'route'       => 'reports.upcoming-cheques',
            ],
            'bank-wise' => [
                'title'       => 'Bank-wise Summary Report',
                'description' => 'Cheque totals grouped and analysed by bank.',
                'icon'        => 'fa-solid fa-building-columns',
                'color'       => 'purplePay',
                'status'      => null,
                'upcoming'    => false,
                'route'       => 'reports.bank-wise',
            ],
            'customer-wise' => [
                'title'       => 'Customer-wise Cheque Report',
                'description' => 'Customer grouped cheque count, totals, and cheque details.',
                'icon'        => 'fa-solid fa-users',
                'color'       => 'teal',
                'status'      => null,
                'upcoming'    => false,
                'route'       => 'reports.customer-wise',
            ],
            'supplier-wise' => [
                'title'       => 'Supplier-wise Cheque Report',
                'description' => 'Supplier grouped cheque count, totals, and issued cheque details.',
                'icon'        => 'fa-solid fa-truck-field',
                'color'       => 'purplePay',
                'status'      => null,
                'upcoming'    => false,
                'route'       => 'reports.supplier-wise',
            ],
            'monthly-summary' => [
                'title'       => 'Monthly Summary Report',
                'description' => 'Monthly cheque volumes and value summary.',
                'icon'        => 'fa-solid fa-chart-bar',
                'color'       => 'navy',
                'status'      => null,
                'upcoming'    => false,
                'route'       => 'reports.monthly-summary',
            ],
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $reports = $this->reports();

        // Attach live quick-stats to each report card
        foreach ($reports as $key => &$meta) {
            $q = Cheque::query();
            if ($meta['status'])   $q->where('status', $meta['status']);
            if ($meta['upcoming']) $q->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])->whereDate('cheque_date', '>=', today());
            $meta['count']  = $q->count();
            $meta['amount'] = $q->sum('amount');
        }

        return view('reports.index', compact('reports'));
    }

    // ── Report Pages ──────────────────────────────────────────────────────────

    public function allCheques(Request $request): View
    {
        return $this->reportPage($request, 'all-cheques');
    }

    public function pendingCheques(Request $request): View
    {
        return $this->reportPage($request, 'pending-cheques');
    }

    public function passedCheques(Request $request): View
    {
        return $this->reportPage($request, 'passed-cheques');
    }

    public function returnedCheques(Request $request): View
    {
        return $this->reportPage($request, 'returned-cheques');
    }

    public function upcomingCheques(Request $request): View
    {
        return $this->reportPage($request, 'upcoming-cheques');
    }

    public function bankWiseCheques(Request $request): View
    {
        return $this->reportPage($request, 'bank-wise');
    }

    public function customerWiseCheques(Request $request): View
    {
        return $this->reportPage($request, 'customer-wise');
    }

    public function supplierWiseCheques(Request $request): View
    {
        return $this->reportPage($request, 'supplier-wise');
    }

    public function monthlySummary(Request $request): View
    {
        return $this->reportPage($request, 'monthly-summary');
    }

    // ── PDF Export (print-friendly HTML) ─────────────────────────────────────

    public function exportPdf(string $reportType, Request $request): View
    {
        $meta    = $this->reports()[$reportType] ?? $this->reports()['all-cheques'];
        $query   = $this->buildQuery($request, $meta);
        $cheques = $query->latest('cheque_date')->get();

        // Bank-wise grouping
        $bankGroups = null;
        if ($reportType === 'bank-wise') {
            $bankGroups = $cheques->groupBy('bank_name')->map(fn ($g) => [
                'cheques' => $g,
                'count'   => $g->count(),
                'amount'  => $g->sum('amount'),
            ])->sortByDesc(fn ($g) => $g['amount']);
        }

        $partyGroups = null;
        if (in_array($reportType, ['customer-wise', 'supplier-wise'], true)) {
            $partyGroups = $this->partyGroups($cheques, $reportType === 'customer-wise' ? 'customer' : 'supplier');
        }

        // Monthly grouping
        $monthGroups = null;
        if ($reportType === 'monthly-summary') {
            $monthGroups = $cheques->groupBy(fn ($c) => $c->cheque_date?->format('Y-m'))
                ->map(fn ($g) => [
                    'label'    => Carbon::parse($g->first()->cheque_date)->format('F Y'),
                    'cheques'  => $g,
                    'count'    => $g->count(),
                    'amount'   => $g->sum('amount'),
                    'customer' => $g->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
                    'own'      => $g->where('cheque_type', Cheque::TYPE_OWN_ISSUED)->sum('amount'),
                ])
                ->sortKeysDesc();
        }

        $settings = [
            'company_name'    => ChequeSetting::getValue('company_name', 'Cheque Management System'),
            'company_phone'   => ChequeSetting::getValue('company_phone', ''),
            'company_email'   => ChequeSetting::getValue('company_email', ''),
            'company_address' => ChequeSetting::getValue('company_address', ''),
            'letterhead_title'=> ChequeSetting::getValue('letterhead_title', 'Cheque Management System'),
            'footer_text'     => ChequeSetting::getValue('footer_text', 'Generated by Cheque Management System | Powered by Twinsofte.com'),
            'signature_name'  => ChequeSetting::getValue('signature_name', 'Authorized Signature'),
        ];

        return view('reports.pdf.layout', [
            'reportTitle'  => $meta['title'],
            'reportType'   => $reportType,
            'cheques'      => $cheques,
            'bankGroups'   => $bankGroups,
            'partyGroups'  => $partyGroups,
            'monthGroups'  => $monthGroups,
            'totalAmount'  => $cheques->sum('amount'),
            'customerAmount'=> $cheques->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
            'ownAmount'    => $cheques->where('cheque_type', Cheque::TYPE_OWN_ISSUED)->sum('amount'),
            'filters'      => array_filter($request->only(['from_date','to_date','cheque_type','bank','cheque_no','amount_min','amount_max','period'])),
            'generatedBy'  => $request->user()?->name ?? 'System',
            'settings'     => $settings,
        ]);
    }

    // ── CSV Export ────────────────────────────────────────────────────────────

    public function exportExcel(string $reportType, Request $request): Response
    {
        $meta    = $this->reports()[$reportType] ?? $this->reports()['all-cheques'];
        $query   = $this->buildQuery($request, $meta);
        $cheques = $query->latest('cheque_date')->get();

        if (in_array($reportType, ['customer-wise', 'supplier-wise'], true)) {
            $groupType = $reportType === 'customer-wise' ? 'customer' : 'supplier';
            $rows = ['Group,Cheque Count,Group Total,Cheque No,Cheque Type,Customer,Supplier,Bank,Branch,Cheque Date,Amount,Status,Notes'];

            foreach ($this->partyGroups($cheques, $groupType) as $groupName => $group) {
                foreach ($group['cheques'] as $c) {
                    $rows[] = implode(',', [
                        '"' . str_replace('"', '""', $groupName) . '"',
                        $group['count'],
                        number_format((float) $group['amount'], 2, '.', ''),
                        $c->cheque_no,
                        '"' . str_replace('"', '""', $this->chequeTypeText($c)) . '"',
                        '"' . str_replace('"', '""', $c->customer?->name ?? $c->originalCustomer?->name ?? '') . '"',
                        '"' . str_replace('"', '""', $c->supplier?->name ?? $c->givenToSupplier?->name ?? '') . '"',
                        '"' . str_replace('"', '""', $c->bank_name) . '"',
                        '"' . str_replace('"', '""', $c->branch_name ?? '') . '"',
                        $c->cheque_date?->format('Y-m-d'),
                        number_format((float) $c->amount, 2, '.', ''),
                        $c->status,
                        '"' . str_replace('"', '""', $c->notes ?? '') . '"',
                    ]);
                }
            }

            $filename = $reportType . '-' . now()->format('Y-m-d') . '.csv';

            return response(implode("\n", $rows))
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $rows = ["Cheque No,Cheque Type,Customer/Supplier,Bank,Branch,Cheque Date,Amount,Status,Notes"];
        foreach ($cheques as $c) {
            $typeText = $this->chequeTypeText($c);

            $custSupplierText = '';
            if ($c->cheque_type === Cheque::TYPE_TRANSFER) {
                $custSupplierText = 'From Customer: ' . ($c->customer?->name ?? '—') . ' / To Supplier: ' . ($c->supplier?->name ?? '—');
            } elseif ($c->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $c->is_transferred_to_supplier) {
                $custSupplierText = ($c->customer?->name ?? '—') . ' / Given to Supplier: ' . ($c->givenToSupplier?->name ?? '—');
            } else {
                $custSupplierText = $c->customer?->name ?? $c->supplier?->name ?? '—';
            }

            $rows[] = implode(',', [
                $c->cheque_no,
                $typeText,
                '"' . str_replace('"', '""', $custSupplierText) . '"',
                '"' . str_replace('"', '""', $c->bank_name) . '"',
                '"' . str_replace('"', '""', $c->branch_name ?? '') . '"',
                $c->cheque_date?->format('Y-m-d'),
                number_format((float) $c->amount, 2, '.', ''),
                $c->status,
                '"' . str_replace('"', '""', $c->notes ?? '') . '"',
            ]);
        }

        $filename = $reportType . '-' . now()->format('Y-m-d') . '.csv';

        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // ── Private: Report Page Builder ──────────────────────────────────────────

    private function reportPage(Request $request, string $key): View
    {
        $meta  = $this->reports()[$key];
        $query = $this->buildQuery($request, $meta);

        $totalQuery    = clone $query;
        $customerQuery = clone $query;
        $ownQuery      = clone $query;

        $cheques = $query->latest('cheque_date')->paginate(15)->withQueryString();

        // Bank-wise grouping for bank report
        $bankGroups = null;
        if ($key === 'bank-wise') {
            $allForGroup = $this->buildQuery($request, $meta)->latest('cheque_date')->get();
            $bankGroups = $allForGroup->groupBy('bank_name')->map(fn ($g) => [
                'cheques'  => $g,
                'count'    => $g->count(),
                'amount'   => $g->sum('amount'),
                'pending'  => $g->where('status', Cheque::STATUS_PENDING)->sum('amount'),
                'passed'   => $g->where('status', Cheque::STATUS_PASSED)->sum('amount'),
                'returned' => $g->where('status', Cheque::STATUS_RETURNED)->sum('amount'),
            ])->sortByDesc(fn ($g) => $g['amount']);
        }

        $partyGroups = null;
        if (in_array($key, ['customer-wise', 'supplier-wise'], true)) {
            $allForGroup = $this->buildQuery($request, $meta)->latest('cheque_date')->get();
            $partyGroups = $this->partyGroups($allForGroup, $key === 'customer-wise' ? 'customer' : 'supplier');
        }

        // Monthly grouping
        $monthGroups = null;
        if ($key === 'monthly-summary') {
            $allForGroup = $this->buildQuery($request, $meta)->latest('cheque_date')->get();
            $monthGroups = $allForGroup->groupBy(fn ($c) => $c->cheque_date?->format('Y-m'))
                ->map(fn ($g) => [
                    'label'    => Carbon::parse($g->first()->cheque_date)->format('F Y'),
                    'count'    => $g->count(),
                    'amount'   => $g->sum('amount'),
                    'customer' => $g->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
                    'own'      => $g->where('cheque_type', Cheque::TYPE_OWN_ISSUED)->sum('amount'),
                    'pending'  => $g->where('status', Cheque::STATUS_PENDING)->sum('amount'),
                    'passed'   => $g->where('status', Cheque::STATUS_PASSED)->sum('amount'),
                    'returned' => $g->where('status', Cheque::STATUS_RETURNED)->sum('amount'),
                ])
                ->sortKeysDesc();
        }

        return view('reports.report', [
            'reportKey'      => $key,
            'meta'           => $meta,
            'cheques'        => $cheques,
            'bankGroups'     => $bankGroups,
            'partyGroups'    => $partyGroups,
            'monthGroups'    => $monthGroups,
            'totalAmount'    => $totalQuery->sum('amount'),
            'totalCount'     => $totalQuery->count(),
            'customerAmount' => $customerQuery->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
            'ownAmount'      => $ownQuery->where('cheque_type', Cheque::TYPE_OWN_ISSUED)->sum('amount'),
            'pendingAmount'  => (clone $totalQuery)->where('status', Cheque::STATUS_PENDING)->sum('amount'),
            'passedAmount'   => (clone $totalQuery)->where('status', Cheque::STATUS_PASSED)->sum('amount'),
            'returnedAmount' => (clone $totalQuery)->where('status', Cheque::STATUS_RETURNED)->sum('amount'),
            'banks'          => Cheque::distinct()->orderBy('bank_name')->pluck('bank_name'),
            'reports'        => $this->reports(),
        ]);
    }

    // ── Private: Query Builder ────────────────────────────────────────────────

    private function buildQuery(Request $request, array $meta)
    {
        $query = Cheque::with(['customer', 'supplier', 'originalCustomer', 'givenToSupplier']);

        // Status filter from report config
        if ($meta['status']) {
            $query->where('status', $meta['status']);
        }

        // Upcoming filter
        if ($meta['upcoming']) {
            $query->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                  ->whereDate('cheque_date', '>=', today());
        }

        // Quick period filter (chips)
        $period = $request->input('period');
        if ($period && ! $request->filled('from_date') && ! $request->filled('to_date')) {
            match ($period) {
                'today'      => $query->whereDate('cheque_date', today()),
                'this_week'  => $query->whereBetween('cheque_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'this_month' => $query->whereMonth('cheque_date', now()->month)->whereYear('cheque_date', now()->year),
                'this_year'  => $query->whereYear('cheque_date', now()->year),
                'last_month' => $query->whereMonth('cheque_date', now()->subMonth()->month)->whereYear('cheque_date', now()->subMonth()->year),
                default      => null,
            };
        }

        // Manual date range
        $query
            ->when($request->filled('from_date'),   fn ($q) => $q->whereDate('cheque_date', '>=', $request->from_date))
            ->when($request->filled('to_date'),     fn ($q) => $q->whereDate('cheque_date', '<=', $request->to_date))
            ->when($request->filled('cheque_type'), fn ($q) => $q->where('cheque_type', $request->cheque_type))
            ->when($request->filled('bank'),        fn ($q) => $q->where('bank_name', $request->bank))
            ->when($request->filled('cheque_no'),   fn ($q) => $q->where('cheque_no', 'like', '%' . $request->cheque_no . '%'))
            ->when($request->filled('amount_min'),  fn ($q) => $q->where('amount', '>=', $request->amount_min))
            ->when($request->filled('amount_max'),  fn ($q) => $q->where('amount', '<=', $request->amount_max));

        if ($meta['route'] === 'reports.customer-wise') {
            $query->where(function ($q) {
                $q->whereNotNull('customer_id')
                  ->orWhereNotNull('original_customer_id');
            });
        } elseif ($meta['route'] === 'reports.supplier-wise') {
            $query->where(function ($q) {
                $q->whereNotNull('supplier_id')
                  ->orWhereNotNull('given_to_supplier_id');
            });
        }

        return $query;
    }

    private function partyGroups($cheques, string $type)
    {
        return $cheques
            ->groupBy(function (Cheque $cheque) use ($type) {
                if ($type === 'customer') {
                    return $cheque->customer?->name
                        ?? $cheque->originalCustomer?->name
                        ?? 'No Customer';
                }

                return $cheque->supplier?->name
                    ?? $cheque->givenToSupplier?->name
                    ?? 'No Supplier';
            })
            ->map(fn ($g) => [
                'cheques' => $g,
                'count' => $g->count(),
                'amount' => $g->sum('amount'),
                'customer' => $g->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)->sum('amount'),
                'own' => $g->where('cheque_type', Cheque::TYPE_OWN_ISSUED)->sum('amount'),
                'pending' => $g->where('status', Cheque::STATUS_PENDING)->sum('amount'),
                'passed' => $g->where('status', Cheque::STATUS_PASSED)->sum('amount'),
                'returned' => $g->where('status', Cheque::STATUS_RETURNED)->sum('amount'),
            ])
            ->sortByDesc(fn ($g) => $g['amount']);
    }

    private function chequeTypeText(Cheque $cheque): string
    {
        if ($cheque->cheque_type === Cheque::TYPE_OWN_ISSUED && $cheque->supplier_cheque_mode === 'received_customer_cheque') {
            return 'Issued to Supplier - Customer Received Cheque';
        }

        return match ($cheque->cheque_type) {
            Cheque::TYPE_CUSTOMER_RECEIVED => 'Customer Received',
            Cheque::TYPE_OWN_ISSUED => 'Own Issued',
            Cheque::TYPE_TRANSFER => 'Customer Cheque Given to Supplier',
            default => ucfirst($cheque->cheque_type),
        };
    }
}
