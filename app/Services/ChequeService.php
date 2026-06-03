<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\ChequeAttachment;
use App\Models\ChequeAuditLog;
use App\Models\ChequeTransaction;
use App\Models\CustomerLedger;
use App\Models\SupplierLedger;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ChequeService
{
    public function create(array $data, ?User $user, ?UploadedFile $attachment = null): Cheque
    {
        $data['status'] = $data['status'] ?? Cheque::STATUS_PENDING;
        $data['created_by'] = $user?->id;
        $data['updated_by'] = $user?->id;

        $cheque = Cheque::create($data);

        if ($attachment) {
            $this->storeAttachment($cheque, $attachment, $user);
        }

        $this->recordTransaction($cheque, 'created', null, $cheque->status, $cheque->amount, null, $user);
        $this->recordAudit($cheque, 'created', [], $cheque->toArray(), $user, null);
        $this->recordLedger($cheque, $cheque->status, null, $user);

        return $cheque;
    }

    public function update(Cheque $cheque, array $data, ?User $user, ?UploadedFile $attachment = null): Cheque
    {
        $before = $cheque->getOriginal();

        $cheque->fill($data);
        $cheque->updated_by = $user?->id;
        $cheque->save();

        if ($attachment) {
            $this->storeAttachment($cheque, $attachment, $user);
        }

        $changed = Arr::only($cheque->getChanges(), array_keys($data));
        $oldValues = Arr::only($before, array_keys($changed));

        if (!empty($changed)) {
            $this->recordAudit($cheque, 'updated', $oldValues, $changed, $user, null);
        }

        return $cheque;
    }

    public function changeStatus(Cheque $cheque, string $newStatus, array $meta, ?User $user): Cheque
    {
        $oldStatus = $cheque->status;

        $update = [
            'status' => $newStatus,
            'updated_by' => $user?->id,
        ];

        if ($newStatus === Cheque::STATUS_DEPOSITED) {
            $update['deposited_date'] = $meta['date'] ?? now()->toDateString();
        }

        if ($newStatus === Cheque::STATUS_PASSED) {
            $update['passed_date'] = $meta['date'] ?? now()->toDateString();
        }

        if ($newStatus === Cheque::STATUS_RETURNED) {
            $update['returned_date'] = $meta['date'] ?? now()->toDateString();
            $update['returned_reason'] = $meta['returned_reason'] ?? $cheque->returned_reason;
            $update['return_charge'] = $meta['return_charge'] ?? $cheque->return_charge;
        }

        if ($newStatus === Cheque::STATUS_CANCELLED) {
            $update['cancelled_date'] = $meta['date'] ?? now()->toDateString();
        }

        $cheque->fill($update);
        $cheque->save();

        $this->recordTransaction(
            $cheque,
            'status_changed',
            $oldStatus,
            $newStatus,
            $cheque->amount,
            $meta['note'] ?? null,
            $user
        );

        $this->recordAudit(
            $cheque,
            'status_changed',
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $user,
            $meta['note'] ?? null
        );

        $this->recordLedger($cheque, $newStatus, $oldStatus, $user);

        return $cheque;
    }

    public function approve(Cheque $cheque, ?User $user, ?string $note = null): Cheque
    {
        $cheque->approved_by = $user?->id;
        $cheque->approved_at = now();
        $cheque->updated_by = $user?->id;
        $cheque->save();

        $this->recordTransaction($cheque, 'approved', $cheque->status, $cheque->status, $cheque->amount, $note, $user);
        $this->recordAudit($cheque, 'approved', [], ['approved_by' => $user?->id], $user, $note);

        return $cheque;
    }

    private function storeAttachment(Cheque $cheque, UploadedFile $attachment, ?User $user): void
    {
        $path = $attachment->store('cheques', 'public');

        $cheque->attachment = $path;
        $cheque->save();

        ChequeAttachment::create([
            'cheque_id' => $cheque->id,
            'file_path' => $path,
            'file_type' => $attachment->getClientMimeType(),
            'uploaded_by' => $user?->id,
        ]);
    }

    private function recordTransaction(
        Cheque $cheque,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?float $amount,
        ?string $note,
        ?User $user
    ): void {
        ChequeTransaction::create([
            'cheque_id' => $cheque->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'amount' => $amount,
            'note' => $note,
            'created_by' => $user?->id,
        ]);
    }

    private function recordAudit(
        Cheque $cheque,
        string $action,
        array $oldValues,
        array $newValues,
        ?User $user,
        ?string $note
    ): void {
        ChequeAuditLog::create([
            'cheque_id' => $cheque->id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'device' => request()?->userAgent(),
            'note' => $note,
        ]);
    }

    private function recordLedger(Cheque $cheque, string $newStatus, ?string $oldStatus, ?User $user): void
    {
        if ($cheque->isCustomerReceived()) {
            $this->recordCustomerLedger($cheque, $newStatus, $oldStatus, $user);
        }

        if ($cheque->isOwnIssued() || $cheque->cheque_type === Cheque::TYPE_TRANSFER) {
            $this->recordSupplierLedger($cheque, $newStatus, $oldStatus, $user);
        }
    }

    private function recordCustomerLedger(Cheque $cheque, string $newStatus, ?string $oldStatus, ?User $user): void
    {
        if (!$cheque->customer_id) {
            return;
        }

        $entryType = match ($newStatus) {
            Cheque::STATUS_DEPOSITED => 'cheque_deposited',
            Cheque::STATUS_PASSED => 'cheque_passed',
            Cheque::STATUS_RETURNED => 'cheque_returned',
            Cheque::STATUS_CANCELLED => 'cheque_cancelled',
            Cheque::STATUS_HOLD => 'cheque_hold',
            default => 'cheque_received',
        };

        $balanceDelta = 0.0;

        if ($newStatus === Cheque::STATUS_PASSED) {
            $balanceDelta = -1 * (float) $cheque->amount;
        }

        if ($newStatus === Cheque::STATUS_RETURNED) {
            $balanceDelta = (float) $cheque->amount;
        }

        if ($oldStatus === Cheque::STATUS_PASSED && in_array($newStatus, [Cheque::STATUS_RETURNED, Cheque::STATUS_CANCELLED], true)) {
            $balanceDelta = (float) $cheque->amount;
        }

        CustomerLedger::create([
            'customer_id' => $cheque->customer_id,
            'entry_type' => $entryType,
            'amount' => $cheque->amount,
            'balance_delta' => $balanceDelta,
            'reference_type' => Cheque::class,
            'reference_id' => $cheque->id,
            'note' => null,
            'created_by' => $user?->id,
        ]);

        if ($newStatus === Cheque::STATUS_RETURNED && (float) $cheque->return_charge > 0) {
            CustomerLedger::create([
                'customer_id' => $cheque->customer_id,
                'entry_type' => 'return_charge',
                'amount' => $cheque->return_charge,
                'balance_delta' => (float) $cheque->return_charge,
                'reference_type' => Cheque::class,
                'reference_id' => $cheque->id,
                'note' => 'Return charge',
                'created_by' => $user?->id,
            ]);
        }
    }

    private function recordSupplierLedger(Cheque $cheque, string $newStatus, ?string $oldStatus, ?User $user): void
    {
        if (!$cheque->supplier_id) {
            return;
        }

        $entryType = match ($newStatus) {
            Cheque::STATUS_PASSED => 'cheque_passed',
            Cheque::STATUS_RETURNED => 'cheque_returned',
            Cheque::STATUS_CANCELLED => 'cheque_cancelled',
            Cheque::STATUS_HOLD => 'cheque_hold',
            default => 'cheque_issued',
        };

        $balanceDelta = 0.0;

        if ($newStatus === Cheque::STATUS_PASSED) {
            $balanceDelta = -1 * (float) $cheque->amount;
        }

        if ($oldStatus === Cheque::STATUS_PASSED && in_array($newStatus, [Cheque::STATUS_RETURNED, Cheque::STATUS_CANCELLED], true)) {
            $balanceDelta = (float) $cheque->amount;
        }

        SupplierLedger::create([
            'supplier_id' => $cheque->supplier_id,
            'entry_type' => $entryType,
            'amount' => $cheque->amount,
            'balance_delta' => $balanceDelta,
            'reference_type' => Cheque::class,
            'reference_id' => $cheque->id,
            'note' => null,
            'created_by' => $user?->id,
        ]);
    }
}
