@props(['status'])

@php
    $styles = [
        'ok' => 'bg-emerald-50 text-emerald-700',
        'low' => 'bg-amber-50 text-amber-700',
        'out' => 'bg-red-50 text-red-700',
    ];
@endphp

<span class="inline-block rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap {{ $styles[$status] }}">
    {{ __('product.stock_status.'.$status) }}
</span>
