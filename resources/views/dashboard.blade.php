@extends('layouts.app')
@section('title', __('nav.dashboard'))

@section('content')
    <div class="space-y-6">
        @php
            // Static classes only — Tailwind cannot compile a name it never sees in the source.
            $columns = [1 => 'xl:grid-cols-1', 2 => 'xl:grid-cols-2', 3 => 'xl:grid-cols-3',
                        4 => 'xl:grid-cols-4', 5 => 'xl:grid-cols-5'][count($cards)] ?? 'xl:grid-cols-5';
        @endphp

        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 {{ $columns }}">
            @foreach ($cards as $card)
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm text-slate-500">{{ __('dashboard.'.$card['key']) }}</div>
                    <div class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ money($card['value']) }}
                        <span class="text-sm font-normal text-slate-400">{{ settings('currency.symbol') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-medium">{{ __('dashboard.low_stock') }}</h2>
                    @if ($lowStock->isNotEmpty())
                        <a href="{{ route('inventory.index', ['status' => 'low']) }}"
                           class="text-sm text-slate-500 hover:underline">{{ __('stock.show_all') }}</a>
                    @endif
                </div>

                @forelse ($lowStock as $product)
                    <a href="{{ route('inventory.adjust', $product) }}"
                       class="flex items-center justify-between gap-3 py-2 border-b last:border-0 hover:bg-slate-50">
                        <span class="truncate">{{ $product->name }}</span>
                        <span class="flex items-center gap-2 whitespace-nowrap">
                            <span class="tabular-nums font-semibold">{{ (float) $product->stock }}</span>
                            <x-stock-badge :status="$product->stock_status" />
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('dashboard.empty') }}</p>
                @endforelse
            </section>
            <section class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-medium">{{ __('dashboard.recent_sales') }}</h2>
                    @can('sale.view')
                        <a href="{{ route('sales.index') }}" class="text-sm text-slate-500 hover:underline">
                            {{ __('stock.show_all') }}
                        </a>
                    @endcan
                </div>

                @forelse ($recentSales as $sale)
                    <a href="{{ route('sales.show', $sale) }}"
                       class="flex items-center justify-between gap-3 py-2 border-b last:border-0 hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate">{{ $sale->customer?->name ?? __('sale.walk_in') }}</span>
                            <span class="block text-xs text-slate-400 tabular-nums">{{ $sale->sold_at->format('H:i') }}</span>
                        </span>
                        <span class="tabular-nums font-medium whitespace-nowrap">{{ money($sale->total) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('dashboard.empty') }}</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
