<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_id',
        'sms_template_id',
        'recipient_type',
        'recipient_id',
        'phone',
        'message',
        'provider',
        'ref',
        'response',
        'status',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if an SMS for this cheque + template + reminder day was already sent today.
     */
    public static function alreadySentToday(int $chequeId, string $recipientType, ?int $templateId): bool
    {
        return static::query()
            ->where('cheque_id', $chequeId)
            ->where('recipient_type', $recipientType)
            ->where('sms_template_id', $templateId)
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->exists();
    }
}
