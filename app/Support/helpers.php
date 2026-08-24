<?php

use App\Support\Settings;

if (! function_exists('settings')) {
    function settings(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Centimes → "1 250,00". The currency symbol is added by the caller. */
    function money(int $centimes): string
    {
        return number_format($centimes / 100, (int) Settings::get('currency.decimals', 2), ',', ' ');
    }
}

if (! function_exists('centimes')) {
    /** "1250,50" or 1250.5 → 125050. Money is never stored as a float. */
    function centimes(int|float|string $amount): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $amount)) * 100);
    }
}
