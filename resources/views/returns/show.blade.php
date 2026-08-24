@extends('layouts.app')
@section('title', $return->reference)

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xl font-semibold tabular-nums">{{ $return->reference }}</div>
                <div class="text-slate-600">
                    <a href="{{ route('sales.show', $return->sale) }}" class="hover:underline tabular-nums">
                        {{ $return->sale->invoice_number }}
                    </a>
                    · {{ $return->customer?->name ?? '/' }}
                </div>
            </div>
            <div class="text-sm text-slate-500 text-end">
                <div class="tabular-nums">{{ $return->returned_at->format('Y-m-d H:i') }}</div>
                <div>{{ $return->user?->name }}</div>
            </div>
        </div>

        <div class="table-card table-scroll">
            <table class="table min-w-[420px]">
                <thead>
                    <tr>
                        <th>{{ __('product.fields.name') }}</th>
                        <th class="num">{{ __('sale.fields.quantity') }}</th>
                        <th class="mid">{{ __('return.condition') }}</th>
                        <th class="num">{{ __('purchase.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($return->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="num">{{ (float) $item->quantity }}</td>
                            <td class="mid">
                                <span class="rounded-full px-2 py-0.5 text-xs
                                    {{ $item->condition === 'damaged' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ __('return.conditions.'.$item->condition) }}
                                </span>
                            </td>
                            <td class="num font-medium">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 space-y-2 text-sm">
            <div class="flex justify-between text-lg font-semibold">
                <span>{{ __('return.refund_total') }}</span>
                <span class="tabular-nums">{{ money($return->total_amount) }} {{ settings('currency.symbol') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('return.refund_method') }}</span>
                <span>{{ __('return.refunds.'.$return->refund_method) }}</span>
            </div>
            @if ($return->reason)
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('return.reason') }}</span>
                    <span>{{ $return->reason }}</span>
                </div>
            @endif
        </div>

        @if ($return->exchangeSale)
            <a href="{{ route('sales.show', $return->exchangeSale) }}"
               class="block rounded-xl bg-sky-50 p-4 text-sky-800 hover:bg-sky-100">
                {{ __('return.exchange_sale') }}
                <span class="font-semibold tabular-nums">{{ $return->exchangeSale->invoice_number }}</span>
            </a>
        @endif
    </div>

    <div class="flex items-center gap-1 bg-white rounded-2xl shadow-sm p-2">
        <x-action icon="view" :label="__('return.list')" :href="route('returns.index')" />
        <x-action icon="add" :label="__('return.new')" tone="primary" :href="route('returns.create')" />
    </div>
</div>
@endsection
