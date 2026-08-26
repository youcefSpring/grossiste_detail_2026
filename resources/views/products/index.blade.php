@extends('layouts.app')
@section('title', __('nav.products'))

@section('content')
<div class="space-y-4" data-live-root>

    {{-- Filters --}}
    <form method="GET" data-live class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2 items-center">
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

        @include('partials.per-page')

        @can('product.manage')
            <a href="{{ route('products.create') }}" data-modal data-modal-title="{{ __('product.add') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('product.add') }}</a>
        @endcan
    </form>

    {{-- Desktop table --}}
    <div class="table-card table-scroll">
        <table class="table table-stack">
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
                    <tr class="{{ ! $product->is_active ? 'row-muted' : ($product->stock_status === 'out' ? 'row-danger' : ($product->stock_status === 'low' ? 'row-warn' : '')) }}">
                        <td>
                            <div class="font-medium">{{ $product->name }}</div>
                            @if ($product->barcode)
                                <div class="text-xs text-slate-400 tabular-nums">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td class="text-slate-600" data-label="{{ __('product.fields.category_id') }}">{{ $product->category?->name ?? '/' }}</td>
                        <td class="num" data-label="{{ __('product.fields.retail_price') }}"><bdi>{{ money($product->retail_price) }}</bdi></td>
                        <td class="num" data-label="{{ __('product.fields.wholesale_price') }}"><bdi>{{ money($product->wholesale_price) }}</bdi></td>
                        <td class="num" data-label="{{ __('product.fields.stock') }}">
                            <bdi>{{ rtrim(rtrim(number_format($product->stock, 3, ',', ' '), '0'), ',') }}</bdi>
                        </td>
                        <td class="mid" data-label="{{ __('product.status') }}">
                            <x-stock-badge :status="$product->stock_status" />
                        </td>
                        <td class="actions">
                            @can('product.manage')
                                <x-action icon="edit" :label="__('common.edit')"
                                          :href="route('products.edit', $product)"
                                          data-modal data-modal-title="{{ __('product.edit') }}" />

                                <x-action icon="delete" :label="__('common.delete')" tone="danger"
                                          data-modal-delete
                                          data-url="{{ route('products.destroy', $product) }}"
                                          data-message="{{ __('product.delete_confirm') }}" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">{{ __('product.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
@endsection
