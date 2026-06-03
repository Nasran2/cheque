<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'device',
        'note',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
