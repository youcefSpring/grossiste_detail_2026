@extends('layouts.app')
@section('title', $sale->invoice_number)

@section('content')
<div class="space-y-4">

    @if ($sale->isVoided())
        <div class="rounded-2xl bg-red-50 p-4 text-red-800">
            <div class="font-semibold">{{ __('sale.is_voided') }}</div>
            <div class="text-sm">{{ $sale->void_reason }} · {{ $sale->voided_at->format('Y-m-d H:i') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xl font-semibold tabular-nums">{{ $sale->invoice_number }}</div>
                <div class="text-slate-600">
                    @if ($sale->customer)
                        <a href="{{ route('customers.show', $sale->customer) }}" class="hover:underline">{{ $sale->customer->name }}</a>
                    @else
                        /
                    @endif
                    · {{ __('sale.types.'.$sale->type) }}
                </div>
            </div>
            <div class="text-sm text-slate-500 text-end">
                <div class="tabular-nums">{{ $sale->sold_at->format('Y-m-d H:i') }}</div>
                <div>{{ $sale->user?->name }}</div>
            </div>
        </div>

        <div class="table-card table-scroll">
            <table class="table min-w-[420px]">
                <thead>
                    <tr>
                        <th>{{ __('product.fields.name') }}</th>
                        <th class="num">{{ __('sale.fields.quantity') }}</th>
                        <th class="num">{{ __('sale.fields.unit_price') }}</th>
                        <th class="num">{{ __('purchase.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="num"><bdi>{{ (float) $item->quantity }}</bdi></td>
                            <td class="num"><bdi>{{ money($item->unit_price) }}</bdi></td>
                            <td class="num font-medium"><bdi>{{ money($item->line_total) }}</bdi></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 space-y-2 text-sm">
            @if ($sale->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('sale.fields.discount_amount') }}</span>
                    <span class="tabular-nums">− {{ money($sale->discount_amount) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-lg font-semibold">
                <span>{{ __('purchase.total') }}</span>
                <span class="tabular-nums">{{ money($sale->total) }} {{ settings('currency.symbol') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('sale.fields.paid_amount') }}</span>
                <span class="tabular-nums">{{ money($sale->paid_amount) }}</span>
            </div>
            @if ($sale->due_amount > 0)
                <div class="flex justify-between text-amber-700 font-medium">
                    <span>{{ __('purchase.due') }}</span>
                    <span class="tabular-nums">{{ money($sale->due_amount) }}</span>
                </div>
            @endif

            @can('report.financial')
                <div class="flex justify-between border-t pt-2 text-slate-500">
                    <span>{{ __('sale.profit') }}</span>
                    <span class="tabular-nums">{{ money($sale->profit()) }}</span>
                </div>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-1 bg-white rounded-2xl shadow-sm p-2">
        <x-action icon="print" :label="__('sale.print')"
                  :href="route('sales.invoice', $sale)" target="_blank" />

        <x-action icon="pdf" :label="__('report.export_pdf')" tone="danger"
                  :href="route('sales.invoice', [$sale, 'export' => 'pdf'])" />

        <span class="w-px h-6 bg-slate-200 mx-1"></span>

        <x-action icon="add" :label="__('nav.new_sale')" tone="primary" :href="route('sales.create')" />
        <x-action icon="view" :label="__('nav.sales')" :href="route('sales.index')" />

        @can('sale.void')
            @unless ($sale->isVoided())
                <x-action icon="void" :label="__('sale.void')" tone="danger" id="void-btn" class="ms-auto" />
            @endunless
        @endcan
    </div>

    @can('sale.void')
        @unless ($sale->isVoided())
            <form method="POST" action="{{ route('sales.void', $sale) }}" id="void-form"
                  class="hidden bg-white rounded-2xl shadow-sm p-5 space-y-3">
                @csrf
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('sale.void_reason') }} <span class="text-red-500">*</span></span>
                    <input name="reason" required class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    <span class="text-xs text-slate-400">{{ __('sale.void_hint') }}</span>
                </label>
                <button class="rounded-lg bg-red-600 text-white px-6 py-3">{{ __('app.confirm') }}</button>
            </form>
        @endunless
    @endcan
</div>
@endsection

@push('scripts')
<script>
onAppReady(function () {
    $('#void-btn').on('click', function () {
        $('#void-form').removeClass('hidden').find('input[name=reason]').trigger('focus');
        $(this).hide();
    });
});
</script>
@endpush
