<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'template_name',
        'message',
        'status',
        'created_by',
        'updated_by',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Fetch a template by its key, or null if not found.
     */
    public static function getByKey(string $key): ?self
    {
        return static::query()->where('template_key', $key)->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
