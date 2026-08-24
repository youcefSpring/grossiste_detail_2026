<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    /** Algeria-oriented defaults. Advanced features stay OFF. */
    public const DEFAULTS = [
        'shop.name' => 'Grossiste',
        'shop.phone' => '',
        'shop.address' => '',
        'shop.logo' => null,
        'locale.default' => 'ar',
        'currency.code' => 'DZD',
        'currency.symbol' => 'دج',
        'currency.decimals' => 2,
        'tax_enabled' => false,
        'variants_enabled' => false,
        'multi_warehouse_enabled' => false,
        'allow_negative_stock' => false,
        'sale.default_type' => 'retail',
        'sale.default_payment_method' => 'cash',
        'sale.max_discount_percent' => 5,
        'payment.methods' => ['cash', 'credit'],
        'invoice.prefix' => 'INV',
    ];

    /** Resolved once per request — money() alone would otherwise hit the cache store per price. */
    private static ?array $memo = null;

    public static function all(): array
    {
        return self::$memo ??= Cache::rememberForever('settings', function () {
            return array_merge(self::DEFAULTS, Setting::pluck('value', 'key')->all());
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /** Drop the per-request memo. Long-running workers and tests must call this between runs. */
    public static function flush(): void
    {
        self::$memo = null;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
        self::$memo = null;
    }
}
