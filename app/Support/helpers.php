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

if (! function_exists('is_modal')) {
    /** True when the current request was opened inside a modal by modal.js. */
    function is_modal(): bool
    {
        return (bool) request()->header('X-Modal');
    }
}

if (! function_exists('modal_layout')) {
    /** Forms extend this: the bare modal shell in a modal, the app chrome otherwise. */
    function modal_layout(): string
    {
        return is_modal() ? 'layouts.modal' : 'layouts.app';
    }
}

if (! function_exists('per_page')) {
    /**
     * Rows per page, chosen by the user and clamped, so a hand-edited
     * ?per_page=100000 cannot ask the database for the whole table.
     */
    function per_page(int $default = 25): int
    {
        $value = (int) request()->input('per_page', $default);

        return in_array($value, [25, 50, 100, 200], true) ? $value : $default;
    }
}
