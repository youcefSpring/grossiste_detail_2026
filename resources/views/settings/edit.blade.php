@extends('layouts.app')
@section('title', __('nav.settings'))

@section('content')
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf @method('PUT')

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- What prints on the invoice --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <h2 class="font-medium">{{ __('setting.shop') }}</h2>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('setting.fields.shop_name') }} <span class="text-red-500">*</span></span>
            <input name="shop_name" value="{{ old('shop_name', $settings['shop.name']) }}" required
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.shop_phone') }}</span>
                <input name="shop_phone" value="{{ old('shop_phone', $settings['shop.phone']) }}"
                       inputmode="tel" placeholder="0X XX XX XX XX"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.shop_address') }}</span>
                <input name="shop_address" value="{{ old('shop_address', $settings['shop.address']) }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>
        </div>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('setting.fields.logo') }}</span>
            <input type="file" name="logo" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
    </div>

    {{-- Day-to-day selling defaults --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <h2 class="font-medium">{{ __('setting.selling') }}</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.default_type') }}</span>
                <select name="sale_default_type" class="w-full rounded-lg border-slate-300 py-2.5">
                    @foreach (['retail', 'wholesale'] as $type)
                        <option value="{{ $type }}" @selected($settings['sale.default_type'] === $type)>
                            {{ __('sale.types.'.$type) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.max_discount') }}</span>
                <div class="relative">
                    <input name="sale_max_discount_percent" type="number" step="0.5" min="0" max="100" required
                           value="{{ old('sale_max_discount_percent', $settings['sale.max_discount_percent']) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 pe-8 tabular-nums">
                    <span class="absolute inset-y-0 end-3 flex items-center text-sm text-slate-400">%</span>
                </div>
                <span class="text-xs text-slate-400">{{ __('setting.max_discount_hint') }}</span>
            </label>
        </div>

        <div class="space-y-2">
            <span class="text-sm font-medium">{{ __('setting.fields.payment_methods') }}</span>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($methods as $method)
                    <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer
                                  has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="checkbox" name="payment_methods[]" value="{{ $method }}" class="rounded border-slate-300"
                               @checked(in_array($method, $settings['payment.methods'], true))>
                        <span class="text-sm">{{ __('payment.methods.'.$method) }}</span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-slate-400">{{ __('setting.payment_methods_hint') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.currency_symbol') }}</span>
                <input name="currency_symbol" value="{{ old('currency_symbol', $settings['currency.symbol']) }}" required
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.invoice_prefix') }}</span>
                <input name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice.prefix']) }}" required
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 uppercase tabular-nums">
                <span class="text-xs text-slate-400">{{ __('setting.prefix_hint') }}</span>
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('setting.fields.default_locale') }}</span>
                <select name="locale_default" class="w-full rounded-lg border-slate-300 py-2.5">
                    @foreach (['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English'] as $code => $label)
                        <option value="{{ $code }}" @selected($settings['locale.default'] === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    {{-- Everything here stays off unless the shop genuinely needs it --}}
    <details class="bg-white rounded-2xl shadow-sm">
        <summary class="cursor-pointer select-none p-5 font-medium">{{ __('setting.advanced') }}</summary>

        <div class="border-t p-5 space-y-3">
            <p class="text-sm text-slate-500">{{ __('setting.advanced_hint') }}</p>

            @foreach ($toggles as $toggle)
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer
                              has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                    <input type="checkbox" name="{{ $toggle }}" value="1" class="mt-1 rounded border-slate-300"
                           @checked($settings[$toggle])>
                    <span>
                        <span class="block font-medium text-sm">{{ __('setting.toggles.'.$toggle) }}</span>
                        <span class="block text-xs text-slate-500">{{ __('setting.toggle_hints.'.$toggle) }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </details>

    <button class="rounded-lg bg-emerald-600 text-white px-6 py-3 font-medium">{{ __('app.save') }}</button>
</form>
@endsection
