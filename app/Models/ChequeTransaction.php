<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_id',
        'action',
        'old_status',
        'new_status',
        'amount',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }
}
