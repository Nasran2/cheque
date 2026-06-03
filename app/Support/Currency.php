<?php

namespace App\Support;

class Currency
{
    public static function formatLkr(mixed $amount): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return 'Rs ' . number_format($value, 2, '.', ',');
    }
}
