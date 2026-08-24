@php($rtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->invoice_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none; }
            body { background: #fff; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 print:p-0">

<div class="mx-auto max-w-[800px] bg-white p-8 shadow-sm print:shadow-none">

    <div class="flex justify-between items-start gap-4 border-b pb-4">
        <div>
            <div class="text-2xl font-bold">{{ settings('shop.name') }}</div>
            @if (settings('shop.address'))<div class="text-sm text-slate-600">{{ settings('shop.address') }}</div>@endif
            @if (settings('shop.phone'))<div class="text-sm text-slate-600 tabular-nums">{{ settings('shop.phone') }}</div>@endif
        </div>
        <div class="text-end">
            <div class="text-lg font-semibold tabular-nums">{{ $sale->invoice_number }}</div>
            <div class="text-sm text-slate-600 tabular-nums">{{ $sale->sold_at->format('Y-m-d H:i') }}</div>
            @if ($sale->isVoided())
                <div class="mt-1 inline-block rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                    {{ __('sale.is_voided') }}
                </div>
            @endif
        </div>
    </div>

    <div class="py-4 text-sm">
        <span class="text-slate-500">{{ __('sale.fields.customer_id') }}:</span>
        <span class="font-medium">{{ $sale->customer?->name ?? '/' }}</span>
        @if ($sale->customer?->phone)
            <span class="text-slate-500 tabular-nums"> · {{ $sale->customer->phone }}</span>
        @endif
    </div>

    <div class="table-card">
        <table class="table">
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
                        <td class="num"><bdi>{{ money($item->line_total) }}</bdi></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <div class="w-64 space-y-1 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.subtotal') }}</span>
                <span class="tabular-nums">{{ money($sale->subtotal) }}</span>
            </div>
            @if ($sale->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('sale.fields.discount_amount') }}</span>
                    <span class="tabular-nums">− {{ money($sale->discount_amount) }}</span>
                </div>
            @endif
            <div class="flex justify-between border-t pt-1 text-lg font-bold">
                <span>{{ __('purchase.total') }}</span>
                <span class="tabular-nums">{{ money($sale->total) }} {{ settings('currency.symbol') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('sale.fields.paid_amount') }}</span>
                <span class="tabular-nums">{{ money($sale->paid_amount) }}</span>
            </div>
            @if ($sale->due_amount > 0)
                <div class="flex justify-between font-semibold">
                    <span>{{ __('purchase.due') }}</span>
                    <span class="tabular-nums">{{ money($sale->due_amount) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-slate-500">
                <span>{{ __('purchase.fields.method') }}</span>
                <span>{{ __('payment.methods.'.($sale->payments->first()?->method ?? 'cash')) }}</span>
            </div>
        </div>
    </div>

    <div class="mt-8 border-t pt-3 text-center text-xs text-slate-400">
        {{ __('sale.thanks') }} · {{ $sale->user?->name }}
    </div>
</div>

<div class="no-print mx-auto max-w-[800px] mt-4 flex gap-2">
    <button onclick="window.print()" class="rounded-lg bg-slate-900 text-white px-6 py-3">{{ __('sale.print') }}</button>
    <a href="{{ route('sales.show', $sale) }}" class="rounded-lg border bg-white px-6 py-3">{{ __('app.cancel') }}</a>
</div>

</body>
</html>
