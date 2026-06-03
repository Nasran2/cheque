<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\ChequeSetting;
use App\Models\Customer;
use App\Models\Supplier;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $receivableStatuses = [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_RETURNED, Cheque::STATUS_HOLD];
        $payableStatuses = [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_RETURNED, Cheque::STATUS_HOLD];

        $summary = [
            'total_count' => Cheque::count(),
            'total_amount' => Cheque::sum('amount'),
            'pending_count' => Cheque::status(Cheque::STATUS_PENDING)->count(),
            'pending_amount' => Cheque::status(Cheque::STATUS_PENDING)->sum('amount'),
            'passed_count' => Cheque::status(Cheque::STATUS_PASSED)->count(),
            'passed_amount' => Cheque::status(Cheque::STATUS_PASSED)->sum('amount'),
            'returned_count' => Cheque::status(Cheque::STATUS_RETURNED)->count(),
            'returned_amount' => Cheque::status(Cheque::STATUS_RETURNED)->sum('amount'),
            'amount_to_receive' => Cheque::type(Cheque::TYPE_CUSTOMER_RECEIVED)->whereIn('status', $receivableStatuses)->sum('amount'),
            'amount_to_pay' => Cheque::type(Cheque::TYPE_OWN_ISSUED)->whereIn('status', $payableStatuses)->sum('amount'),
        ];

        $recentCheques = Cheque::with(['customer', 'supplier'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('cheque_no', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('cheque_date')
            ->latest()
            ->limit(8)
            ->get();

        $upcomingCheques = Cheque::with(['customer', 'supplier'])
            ->whereDate('cheque_date', '>=', today())
            ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
            ->orderBy('cheque_date')
            ->limit(5)
            ->get();

        $overdueCheques = Cheque::with(['customer', 'supplier'])
            ->whereDate('cheque_date', '<', today())
            ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
            ->orderBy('cheque_date')
            ->limit(5)
            ->get();

        // Retrieve and parse reminder settings
        $custDaysStr = ChequeSetting::getValue('customer_reminder_days', '5');
        $custDaysArr = array_filter(array_map('intval', explode(',', $custDaysStr)));
        $maxCustDays = !empty($custDaysArr) ? max($custDaysArr) : 5;

        $suppDaysStr = ChequeSetting::getValue('supplier_reminder_days', '5');
        $suppDaysArr = array_filter(array_map('intval', explode(',', $suppDaysStr)));
        $maxSuppDays = !empty($suppDaysArr) ? max($suppDaysArr) : 5;

        // Fetch Customer Reminders (received customer cheques, not transferred)
        $customerReminders = collect();
        if (ChequeSetting::getValue('customer_reminders_enabled', '1') === '1') {
            $customerReminders = Cheque::with(['customer'])
                ->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)
                ->where('is_transferred_to_supplier', false)
                ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                ->whereDate('cheque_date', '>=', today()->subDays($maxCustDays))
                ->whereDate('cheque_date', '<=', today()->addDays($maxCustDays))
                ->orderBy('cheque_date')
                ->get();
        }

        // Fetch Supplier Reminders (own issued cheques OR transferred customer cheques)
        $supplierReminders = collect();
        if (ChequeSetting::getValue('supplier_reminders_enabled', '1') === '1') {
            $supplierReminders = Cheque::with(['supplier', 'customer', 'givenToSupplier'])
                ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                ->whereDate('cheque_date', '>=', today()->subDays($maxSuppDays))
                ->whereDate('cheque_date', '<=', today()->addDays($maxSuppDays))
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('cheque_type', Cheque::TYPE_OWN_ISSUED)
                          ->where('supplier_cheque_mode', 'own_cheque');
                    })
                    ->orWhere(function ($q) {
                        $q->where('cheque_type', Cheque::TYPE_CUSTOMER_RECEIVED)
                          ->where('is_transferred_to_supplier', true)
                          ->whereNotNull('given_to_supplier_id');
                    });
                })
                ->orderBy('cheque_date')
                ->get();
        }

        $statusCounts = collect([
            Cheque::STATUS_PENDING,
            Cheque::STATUS_PASSED,
            Cheque::STATUS_RETURNED,
            Cheque::STATUS_HOLD,
            Cheque::STATUS_DEPOSITED,
        ])->mapWithKeys(fn (string $status) => [$status => Cheque::status($status)->count()]);

        $months = collect(CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), '1 month', now()->startOfMonth()))
            ->map(function ($date) {
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                return [
                    'label' => $date->format('M Y'),
                    'count' => Cheque::whereBetween('cheque_date', [$start, $end])->count(),
                    'amount' => (float) Cheque::whereBetween('cheque_date', [$start, $end])->sum('amount'),
                ];
            });

        return view('dashboard', [
            'summary' => $summary,
            'recentCheques' => $recentCheques,
            'upcomingCheques' => $upcomingCheques,
            'overdueCheques' => $overdueCheques,
            'customerReminders' => $customerReminders,
            'supplierReminders' => $supplierReminders,
            'statusCounts' => $statusCounts,
            'months' => $months,
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'customerCount' => Customer::count(),
            'supplierCount' => Supplier::count(),
        ]);
    }
}
