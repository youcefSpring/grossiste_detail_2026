@extends('layouts.app')
@section('title', __('nav.inventory'))

@section('content')
<div class="space-y-4" data-live-root>

    {{-- Status tabs: one glance tells the whole story --}}
    <div class="flex gap-2 overflow-x-auto pb-1">
        @foreach ([['', 'all', 'bg-slate-900'], ['low', 'low', 'bg-amber-500'], ['out', 'out', 'bg-red-600']] as [$value, $key, $tone])
            @php($active = request('status', '') === $value)
            <a href="{{ route('inventory.index', array_filter(['status' => $value, 'q' => request('q'), 'category_id' => request('category_id')])) }}"
               data-live-link
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium
                      {{ $active ? $tone.' text-white' : 'bg-white text-slate-600 shadow-sm' }}">
                {{ __('stock.filters.'.$key) }}
                <span class="ms-1 opacity-70">({{ $counts[$key] }})</span>
            </a>
        @endforeach

        <a href="{{ route('inventory.movements') }}"
           class="ms-auto whitespace-nowrap rounded-full bg-white px-4 py-2 text-sm text-slate-600 shadow-sm">
            {{ __('stock.movements') }}
        </a>
    </div>

    <form method="GET" data-live class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="search" name="q" value="{{ request('q') }}"
               placeholder="{{ __('product.search_placeholder') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-3 py-2.5">

        <select name="category_id" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('product.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>
    </form>

    {{-- Desktop --}}
    <div class="hidden md:block table-card table-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('product.fields.name') }}</th>
                    <th class="num">{{ __('product.fields.stock') }}</th>
                    <th class="num">{{ __('product.fields.min_stock') }}</th>
                    <th class="mid">{{ __('product.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="{{ $product->stock_status === 'out' ? 'row-danger' : ($product->stock_status === 'low' ? 'row-warn' : '') }}">
                        <td>
                            <div class="font-medium">{{ $product->name }}</div>
                            <div class="text-xs text-slate-400">{{ $product->category?->name ?? '/' }}</div>
                        </td>
                        <td class="num font-semibold">
                            {{ (float) $product->stock }}
                            <span class="text-xs font-normal text-slate-400">{{ __('product.units.'.$product->unit) }}</span>
                        </td>
                        <td class="num text-slate-500">{{ (float) $product->min_stock }}</td>
                        <td class="mid"><x-stock-badge :status="$product->stock_status" /></td>
                        <td class="actions">
                            <div class="flex items-center justify-end gap-1">
                                @can('stock.adjust')
                                    <x-action icon="adjust" :label="__('stock.adjust')"
                                              :href="route('inventory.adjust', $product)" />
                                @endcan
                                <x-action icon="history" :label="__('stock.history')"
                                          :href="route('inventory.movements', ['product_id' => $product->id])" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">{{ __('stock.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden space-y-2">
        @forelse ($products as $product)
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ $product->name }}</div>
                        <div class="text-xs text-slate-400">{{ $product->category?->name ?? '/' }}</div>
                    </div>
                    <x-stock-badge :status="$product->stock_status" />
                </div>
                <div class="mt-3 flex items-end justify-between">
                    <div>
                        <div class="text-2xl font-semibold tabular-nums">{{ (float) $product->stock }}</div>
                        <div class="text-xs text-slate-400">
                            {{ __('product.fields.min_stock') }}: {{ (float) $product->min_stock }}
                        </div>
                    </div>
                    @can('stock.adjust')
                        <a href="{{ route('inventory.adjust', $product) }}"
                           class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">{{ __('stock.adjust') }}</a>
                    @endcan
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('stock.none') }}</div>
        @endforelse
    </div>

    {{ $products->links() }}
</div>
@endsection
