@extends('layouts.app')
@section('title', __('return.list'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @can('sale.return')
            <a href="{{ route('returns.create') }}" class="rounded-lg bg-emerald-600 text-white px-5 py-2.5">
                + {{ __('return.new') }}
            </a>
        @endcan
        @can('sale.exchange')
            <a href="{{ route('exchanges.create') }}" class="rounded-lg border bg-white px-5 py-2.5">
                ⇄ {{ __('return.exchange') }}
            </a>
        @endcan
    </div>

    <section class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-medium mb-3">{{ __('return.from_customers') }}</h2>
        <div class="divide-y">
            @forelse ($saleReturns as $item)
                <a href="{{ route('returns.show', $item) }}"
                   class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50">
                    <div>
                        <div class="font-medium tabular-nums">{{ $item->reference }}</div>
                        <div class="text-xs text-slate-400">
                            <span class="tabular-nums">{{ $item->sale->invoice_number }}</span>
                            · {{ $item->customer?->name ?? __('sale.walk_in') }}
                            · {{ $item->returned_at->format('Y-m-d') }}
                        </div>
                    </div>
                    <div class="text-end whitespace-nowrap">
                        <div class="tabular-nums font-medium">{{ money($item->total_amount) }}</div>
                        <div class="text-xs text-slate-400">{{ __('return.refunds.'.$item->refund_method) }}</div>
                    </div>
                </a>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('return.none') }}</p>
            @endforelse
        </div>
        {{ $saleReturns->links() }}
    </section>

    <section class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-medium mb-3">{{ __('return.to_suppliers') }}</h2>
        <div class="divide-y">
            @forelse ($purchaseReturns as $item)
                <a href="{{ route('purchases.show', $item->purchase) }}"
                   class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50">
                    <div>
                        <div class="font-medium tabular-nums">{{ $item->reference }}</div>
                        <div class="text-xs text-slate-400">
                            <span class="tabular-nums">{{ $item->purchase->reference }}</span>
                            · {{ $item->supplier->name }}
                            · {{ $item->returned_at->format('Y-m-d') }}
                        </div>
                    </div>
                    <div class="tabular-nums font-medium whitespace-nowrap">{{ money($item->total_amount) }}</div>
                </a>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('return.none') }}</p>
            @endforelse
        </div>
        {{ $purchaseReturns->links() }}
    </section>
</div>
@endsection
