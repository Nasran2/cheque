<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cheque_id',
        'title',
        'message',
        'type',
        'reminder_day',
        'status',
        'read_at',
        'scheduled_for',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }
}
