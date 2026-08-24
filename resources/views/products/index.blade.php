@extends('layouts.app')
@section('title', __('nav.products'))

@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2 items-center">
        <input type="search" name="q" value="{{ request('q') }}"
               placeholder="{{ __('product.search_placeholder') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-3 py-2.5">

        <select name="category_id" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('product.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @can('product.manage')
            <a href="{{ route('products.create') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('product.add') }}</a>
        @endcan
    </form>

    {{-- Desktop table --}}
    <div class="hidden md:block table-card table-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('product.fields.name') }}</th>
                    <th>{{ __('product.fields.category_id') }}</th>
                    <th class="num">{{ __('product.fields.retail_price') }}</th>
                    <th class="num">{{ __('product.fields.wholesale_price') }}</th>
                    <th class="num">{{ __('product.fields.stock') }}</th>
                    <th class="mid">{{ __('product.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="{{ $product->is_active ? '' : 'opacity-50' }}">
                        <td>
                            <div class="font-medium">{{ $product->name }}</div>
                            @if ($product->barcode)
                                <div class="text-xs text-slate-400 tabular-nums">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td class="text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                        <td class="num">{{ money($product->retail_price) }}</td>
                        <td class="num">{{ money($product->wholesale_price) }}</td>
                        <td class="num">
                            {{ rtrim(rtrim(number_format($product->stock, 3, ',', ' '), '0'), ',') }}
                        </td>
                        <td class="mid">
                            <x-stock-badge :status="$product->stock_status" />
                        </td>
                        <td class="actions">
                            @can('product.manage')
                                <x-action icon="edit" :label="__('common.edit')"
                                          :href="route('products.edit', $product)" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">{{ __('product.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden space-y-2">
        @forelse ($products as $product)
            <a href="{{ route('products.edit', $product) }}" class="block bg-white rounded-2xl shadow-sm p-4">
                <div class="flex justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ $product->name }}</div>
                        <div class="text-xs text-slate-500">{{ $product->category?->name ?? '—' }}</div>
                    </div>
                    <x-stock-badge :status="$product->stock_status" />
                </div>
                <div class="mt-3 flex justify-between text-sm">
                    <span class="text-slate-500">{{ __('product.fields.retail_price') }}</span>
                    <span class="tabular-nums font-medium">{{ money($product->retail_price) }} {{ settings('currency.symbol') }}</span>
                </div>
                <div class="mt-1 flex justify-between text-sm">
                    <span class="text-slate-500">{{ __('product.fields.stock') }}</span>
                    <span class="tabular-nums font-medium">{{ (float) $product->stock }}</span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('product.none') }}</div>
        @endforelse
    </div>

    {{ $products->links() }}
</div>
@endsection
