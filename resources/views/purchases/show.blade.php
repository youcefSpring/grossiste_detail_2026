@extends('layouts.app')
@section('title', $purchase->reference)

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xl font-semibold tabular-nums">{{ $purchase->reference }}</div>
                <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="text-slate-600 hover:underline">
                    {{ $purchase->supplier->name }}
                </a>
            </div>
            <div class="text-sm text-slate-500 text-end">
                <div class="tabular-nums">{{ $purchase->purchased_at->format('Y-m-d') }}</div>
                <div>{{ $purchase->user?->name }}</div>
            </div>
        </div>

        <div class="table-card table-scroll">
            <table class="table min-w-[420px]">
                <thead>
                    <tr>
                        <th>{{ __('product.fields.name') }}</th>
                        <th class="num">{{ __('purchase.fields.quantity') }}</th>
                        <th class="num">{{ __('purchase.fields.unit_cost') }}</th>
                        <th class="num">{{ __('purchase.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="num">{{ (float) $item->quantity }}</td>
                            <td class="num">{{ money($item->unit_cost) }}</td>
                            <td class="num font-medium">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.subtotal') }}</span>
                <span class="tabular-nums">{{ money($purchase->subtotal) }}</span>
            </div>
            @if ($purchase->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('purchase.fields.discount_amount') }}</span>
                    <span class="tabular-nums">− {{ money($purchase->discount_amount) }}</span>
                </div>
            @endif
            <div class="flex justify-between border-t pt-2 text-lg font-semibold">
                <span>{{ __('purchase.total') }}</span>
                <span class="tabular-nums">{{ money($purchase->total) }} {{ settings('currency.symbol') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.fields.paid_amount') }}</span>
                <span class="tabular-nums">{{ money($purchase->paid_amount) }}</span>
            </div>
            <div class="flex justify-between font-medium {{ $purchase->due_amount > 0 ? 'text-amber-700' : 'text-emerald-600' }}">
                <span>{{ __('purchase.due') }}</span>
                <span class="tabular-nums">{{ money($purchase->due_amount) }}</span>
            </div>
        </div>

        @if ($purchase->note)
            <p class="text-sm text-slate-500">{{ $purchase->note }}</p>
        @endif
    </div>

    @if ($purchase->due_amount > 0)
        <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">
            {{ __('purchase.settle_hint') }}
            <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="font-medium underline">
                {{ $purchase->supplier->name }}
            </a>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-1 bg-white rounded-2xl shadow-sm p-2">
        <x-action icon="view" :label="__('nav.purchases')" :href="route('purchases.index')" />

        @can('purchase.return')
            <x-action icon="return" :label="__('return.to_supplier_title')" tone="danger"
                      :href="route('purchase-returns.create', $purchase)" />
        @endcan
    </div>
</div>
@endsection
