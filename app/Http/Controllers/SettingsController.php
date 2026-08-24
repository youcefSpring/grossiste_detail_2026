<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** Everything an owner may change, and how to validate it. */
    private const EDITABLE = [
        'shop.name' => ['required', 'string', 'max:120'],
        'shop.phone' => ['nullable', 'string', 'max:30'],
        'shop.address' => ['nullable', 'string', 'max:190'],
        'currency.symbol' => ['required', 'string', 'max:8'],
        'locale.default' => ['required', 'in:ar,fr,en'],
        'invoice.prefix' => ['required', 'string', 'max:6', 'regex:/^[A-Z]+$/'],
        'sale.default_type' => ['required', 'in:retail,wholesale'],
        'sale.max_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
    ];

    /** Advanced behaviour, all off by default. */
    private const TOGGLES = [
        'tax_enabled', 'variants_enabled', 'multi_warehouse_enabled', 'allow_negative_stock',
    ];

    public function edit()
    {
        return view('settings.edit', [
            'settings' => Settings::all(),
            'methods' => Payment::METHODS,
            'toggles' => self::TOGGLES,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];

        foreach (self::EDITABLE as $key => $rule) {
            $rules[$this->field($key)] = $rule;
        }

        $rules['payment_methods'] = ['required', 'array', 'min:1'];
        $rules['payment_methods.*'] = ['in:'.implode(',', Payment::METHODS)];
        $rules['logo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'];

        $data = $request->validate($rules);

        foreach (self::EDITABLE as $key => $rule) {
            Settings::set($key, $data[$this->field($key)]);
        }

        Settings::set('payment.methods', array_values($data['payment_methods']));

        foreach (self::TOGGLES as $toggle) {
            Settings::set($toggle, $request->boolean($toggle));
        }

        if ($request->hasFile('logo')) {
            Settings::set('shop.logo', $request->file('logo')->store('shop', 'public'));
        }

        return redirect()
            ->route('settings.edit')
            ->with('status', __('setting.saved'));
    }

    /** shop.name → shop_name, so the keys survive an HTML form. */
    private function field(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
