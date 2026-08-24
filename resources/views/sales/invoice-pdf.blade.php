@extends('pdf.layout')
@section('title', $sale->invoice_number)
@section('doc-title', $sale->invoice_number)
@section('doc-meta', $sale->sold_at->format('Y-m-d H:i'))

@section('content')
<p>
    <span class="muted">{{ __('sale.fields.customer_id') }}:</span>
    <span class="bold">{{ $sale->customer?->name ?? __('sale.walk_in') }}</span>
    @if ($sale->customer?->phone)
        <span class="muted small"> · {{ $sale->customer->phone }}</span>
    @endif
    @if ($sale->isVoided())
        <span class="badge"> {{ __('sale.is_voided') }} </span>
    @endif
</p>

<table class="data">
    <thead>
        <tr>
            <th class="start">{{ __('product.fields.name') }}</th>
            <th class="end">{{ __('sale.fields.quantity') }}</th>
            <th class="end">{{ __('sale.fields.unit_price') }}</th>
            <th class="end">{{ __('purchase.line_total') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sale->items as $item)
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                <td class="start">{{ $item->product_name }}</td>
                <td class="end num" dir="ltr">{{ (float) $item->quantity }}</td>
                <td class="end num" dir="ltr">{{ money($item->unit_price) }}</td>
                <td class="end num" dir="ltr">{{ money($item->line_total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table width="100%" style="margin-top: 5mm;">
    <tr>
        <td width="55%"></td>
        <td width="45%" align="{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">
            <table class="totals">
                <tr>
                    <td class="muted">{{ __('purchase.subtotal') }}</td>
                    <td class="end num" dir="ltr">{{ money($sale->subtotal) }}</td>
                </tr>

                @if ($sale->discount_amount > 0)
                    <tr>
                        <td class="muted">{{ __('sale.fields.discount_amount') }}</td>
                        <td class="end num" dir="ltr">− {{ money($sale->discount_amount) }}</td>
                    </tr>
                @endif

                <tr class="grand">
                    <td>{{ __('purchase.total') }}</td>
                    <td class="end"><span class="num" dir="ltr">{{ money($sale->total) }}</span> {{ settings('currency.symbol') }}</td>
                </tr>

                <tr>
                    <td class="muted">{{ __('sale.fields.paid_amount') }}</td>
                    <td class="end num" dir="ltr">{{ money($sale->paid_amount) }}</td>
                </tr>

                @if ($sale->due_amount > 0)
                    <tr>
                        <td class="bold">{{ __('purchase.due') }}</td>
                        <td class="end num bold" dir="ltr">{{ money($sale->due_amount) }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="muted">{{ __('purchase.fields.method') }}</td>
                    <td class="end">{{ __('payment.methods.'.($sale->payments->first()?->method ?? 'cash')) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="foot center muted small">
    {{ __('sale.thanks') }} · {{ $sale->user?->name }}
</div>
@endsection
