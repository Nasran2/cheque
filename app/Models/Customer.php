<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'phone_2',
        'email',
        'nic',
        'vat_no',
        'address',
        'city',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
