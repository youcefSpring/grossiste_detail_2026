@extends('layouts.app')
@section('title', $product->exists ? __('product.edit') : __('product.add'))

@section('content')
<form method="POST" enctype="multipart/form-data" class="space-y-4"
      action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 items-start xl:grid-cols-[minmax(0,1fr)_380px]">

        {{-- The fields a shop actually fills in --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('product.fields.name') }} <span class="text-red-500">*</span></span>
                <input name="name" value="{{ old('name', $product->name) }}" required autofocus
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('product.fields.category_id') }}</span>
                    <select name="category_id" class="w-full rounded-lg border-slate-300 py-2.5">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('product.fields.barcode') }}</span>
                    <input name="barcode" value="{{ old('barcode', $product->barcode) }}" inputmode="numeric"
                           placeholder="{{ __('product.scan_hint') }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['cost_price', 'retail_price', 'wholesale_price'] as $field)
                    <label class="block space-y-1">
                        <span class="text-sm font-medium">
                            {{ __('product.fields.'.$field) }} <span class="text-red-500">*</span>
                        </span>
                        <div class="relative">
                            <input name="{{ $field }}" type="number" step="0.01" min="0" required
                                   value="{{ old($field, $product->exists ? number_format($product->$field / 100, 2, '.', '') : '') }}"
                                   class="w-full rounded-lg border-slate-300 px-3 py-2.5 pe-12 tabular-nums">
                            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-slate-400">
                                {{ settings('currency.symbol') }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('product.fields.stock') }} <span class="text-red-500">*</span></span>
                    <input name="stock" type="number" step="0.001" min="0" required
                           value="{{ old('stock', $stock) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                    @if ($product->exists)
                        <span class="text-xs text-slate-400">{{ __('product.stock_edit_hint') }}</span>
                    @endif
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('product.fields.min_stock') }}</span>
                    <input name="min_stock" type="number" step="0.001" min="0" required
                           value="{{ old('min_stock', (float) $product->min_stock) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                    <span class="text-xs text-slate-400">{{ __('product.min_stock_hint') }}</span>
                </label>
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('product.fields.note') }}</span>
                <textarea name="note" rows="2"
                          class="w-full rounded-lg border-slate-300 px-3 py-2.5">{{ old('note', $product->note) }}</textarea>
            </label>
        </div>

        {{-- Side panel: picture, the folded-away extras, and the actions --}}
        <div class="space-y-4 xl:sticky xl:top-4">
            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
                <span class="text-sm font-medium">{{ __('product.fields.image') }}</span>

                @if ($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt=""
                         class="h-40 w-full rounded-xl object-cover">
                @else
                    <div class="h-40 w-full rounded-xl bg-slate-50 border border-dashed flex items-center justify-center text-4xl text-slate-300">
                        🏷️
                    </div>
                @endif

                <input type="file" name="image" accept="image/*"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <details class="bg-white rounded-2xl shadow-sm"
                     @if ($errors->hasAny(['sku', 'unit', 'min_price'])) open @endif>
                <summary class="cursor-pointer select-none p-5 font-medium text-slate-700">
                    {{ __('product.advanced') }}
                </summary>

                <div class="border-t p-5 space-y-4">
                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('product.fields.sku') }}</span>
                        <input name="sku" value="{{ old('sku', $product->sku) }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('product.fields.unit') }}</span>
                        <select name="unit" class="w-full rounded-lg border-slate-300 py-2.5">
                            @foreach (\App\Models\Product::UNITS as $unit)
                                <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>
                                    {{ __('product.units.'.$unit) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('product.fields.min_price') }}</span>
                        <input name="min_price" type="number" step="0.01" min="0"
                               value="{{ old('min_price', $product->min_price ? number_format($product->min_price / 100, 2, '.', '') : '') }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                        <span class="text-xs text-slate-400">{{ __('product.min_price_hint') }}</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                               @checked(old('is_active', $product->is_active ?? true))>
                        <span class="text-sm">{{ __('product.fields.is_active') }}</span>
                    </label>
                </div>
            </details>

            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
                <a href="{{ route('products.index') }}"
                   class="block text-center rounded-lg border py-3">{{ __('app.cancel') }}</a>

                @if ($product->exists)
                    <button form="delete-product" data-confirm="{{ __('product.delete_confirm') }}"
                            class="w-full rounded-lg text-red-600 py-2 text-sm">{{ __('common.delete') }}</button>
                @endif
            </div>
        </div>
    </div>
</form>

@if ($product->exists)
    <form id="delete-product" method="POST" action="{{ route('products.destroy', $product) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
@endif
@endsection
