<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeSetting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'value', 'type', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function setValue(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        static::query()->updateOrCreate(['key' => $key], [
            'group' => $group,
            'value' => is_array($value) ? implode(',', $value) : $value,
            'type' => $type,
        ]);
    }
}
