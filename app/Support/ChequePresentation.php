<?php

namespace App\Support;

use App\Models\Cheque;

class ChequePresentation
{
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            Cheque::STATUS_PENDING => 'Pending',
            Cheque::STATUS_DEPOSITED => 'Deposited',
            Cheque::STATUS_PASSED => 'Passed',
            Cheque::STATUS_RETURNED => 'Returned',
            Cheque::STATUS_CANCELLED => 'Cancelled',
            Cheque::STATUS_HOLD => 'Hold',
            default => ucfirst($status),
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            Cheque::STATUS_PASSED => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            Cheque::STATUS_PENDING => 'bg-orange-100 text-orange-700 ring-orange-200',
            Cheque::STATUS_DEPOSITED => 'bg-sky-100 text-sky-700 ring-sky-200',
            Cheque::STATUS_RETURNED => 'bg-red-100 text-red-700 ring-red-200',
            Cheque::STATUS_CANCELLED => 'bg-slate-100 text-slate-600 ring-slate-200',
            Cheque::STATUS_HOLD => 'bg-gray-100 text-gray-600 ring-gray-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            Cheque::TYPE_CUSTOMER_RECEIVED => 'Received',
            Cheque::TYPE_OWN_ISSUED => 'Issued',
            Cheque::TYPE_TRANSFER => 'Transferred',
            default => ucfirst($type),
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            Cheque::TYPE_CUSTOMER_RECEIVED => 'bg-blue-100 text-blue-700 ring-blue-200',
            Cheque::TYPE_OWN_ISSUED => 'bg-violet-100 text-violet-700 ring-violet-200',
            Cheque::TYPE_TRANSFER => 'bg-amber-100 text-amber-700 ring-amber-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function displayTypeLabel(Cheque $cheque): string
    {
        if ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier) {
            return 'Transferred';
        }
        return self::typeLabel($cheque->cheque_type);
    }

    public static function displayTypeBadgeClass(Cheque $cheque): string
    {
        if ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier) {
            return 'bg-amber-100 text-amber-700 ring-amber-200';
        }
        return self::typeBadgeClass($cheque->cheque_type);
    }
}
