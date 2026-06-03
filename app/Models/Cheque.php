<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    public const TYPE_CUSTOMER_RECEIVED = 'customer_received';
    public const TYPE_OWN_ISSUED = 'own_issued';
    public const TYPE_TRANSFER = 'customer_cheque_given_to_supplier';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DEPOSITED = 'deposited';
    public const STATUS_PASSED = 'passed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_HOLD = 'hold';

    protected $fillable = [
        'cheque_type',
        'cheque_no',
        'bank_name',
        'branch_name',
        'account_no',
        'cheque_date',
        'received_or_issued_date',
        'amount',
        'customer_id',
        'supplier_id',
        'invoice_id',
        'purchase_id',
        'status',
        'deposited_date',
        'passed_date',
        'returned_date',
        'cancelled_date',
        'returned_reason',
        'return_charge',
        'replacement_cheque_id',
        'attachment',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'original_customer_id',
        'given_to_supplier_id',
        'source_customer_cheque_id',
        'is_transferred_to_supplier',
        'transferred_date',
        'transfer_note',
        'supplier_cheque_mode',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'received_or_issued_date' => 'date',
        'deposited_date' => 'date',
        'passed_date' => 'date',
        'returned_date' => 'date',
        'cancelled_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'return_charge' => 'decimal:2',
        'transferred_date' => 'date',
        'is_transferred_to_supplier' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function originalCustomer()
    {
        return $this->belongsTo(Customer::class, 'original_customer_id');
    }

    public function givenToSupplier()
    {
        return $this->belongsTo(Supplier::class, 'given_to_supplier_id');
    }

    public function sourceCustomerCheque()
    {
        return $this->belongsTo(self::class, 'source_customer_cheque_id');
    }

    public function transferredCheques()
    {
        return $this->hasMany(self::class, 'source_customer_cheque_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions()
    {
        return $this->hasMany(ChequeTransaction::class);
    }

    public function attachments()
    {
        return $this->hasMany(ChequeAttachment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ChequeAuditLog::class);
    }

    public function replacementCheque()
    {
        return $this->belongsTo(self::class, 'replacement_cheque_id');
    }

    public function replacedBy()
    {
        return $this->hasOne(self::class, 'replacement_cheque_id');
    }

    public function scopeType($query, string $type)
    {
        return $query->where('cheque_type', $type);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isCustomerReceived(): bool
    {
        return $this->cheque_type === self::TYPE_CUSTOMER_RECEIVED;
    }

    public function isOwnIssued(): bool
    {
        return $this->cheque_type === self::TYPE_OWN_ISSUED;
    }
}
