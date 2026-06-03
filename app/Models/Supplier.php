<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'phone_2',
        'email',
        'vat_no',
        'address',
        'city',
        'bank_name',
        'bank_branch',
        'account_name',
        'account_no',
        'opening_balance',
        'current_balance',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(SupplierLedger::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
