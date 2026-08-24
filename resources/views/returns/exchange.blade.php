@extends('layouts.app')
@section('title', __('return.exchange'))

@section('content')
<div class="space-y-4">

    @include('returns._lookup')

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if ($sale)
        <form method="POST" action="{{ route('exchanges.store', $sale) }}" id="exchange-form" class="space-y-4">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-semibold tabular-nums">{{ $sale->invoice_number }}</div>
                    <div class="text-sm text-slate-500">
                        {{ $sale->customer?->name ?? '/' }} · {{ $sale->sold_at->format('Y-m-d') }}
                    </div>
                </div>
                <div class="tabular-nums font-medium">{{ money($sale->total) }} {{ settings('currency.symbol') }}</div>
            </div>

            {{-- What comes back --}}
            <h2 class="font-medium px-1">1 · {{ __('return.coming_back') }}</h2>
            @include('returns._lines')

            {{-- What goes out --}}
            <h2 class="font-medium px-1">2 · {{ __('return.going_out') }}</h2>
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
                @include('partials.product-search', ['big' => false])

                <div class="table-card table-scroll">
                    <table class="table table-edit min-w-[480px]">
                        <tbody id="new-lines"></tbody>
                    </table>
                </div>
                <p id="new-empty" class="table-empty">{{ __('sale.cart_empty') }}</p>
            </div>

            {{-- The difference --}}
            <div class="bg-white rounded-2xl shadow-sm p-4 grid gap-4 sm:grid-cols-2">
                <div class="space-y-3">
                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('return.extra_paid') }}</span>
                        <input id="extra" name="extra_paid" type="number" step="0.01" min="0" value="0"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg tabular-nums">
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('purchase.fields.method') }}</span>
                        <select name="method" class="w-full rounded-lg border-slate-300 py-2.5">
                            @foreach (settings('payment.methods', ['cash']) as $method)
                                <option value="{{ $method }}">{{ __('payment.methods.'.$method) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('return.reason') }}</span>
                        <input name="reason" class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    </label>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 space-y-2 text-sm self-start">
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('return.returned_value') }}</span>
                        <span id="t-back" class="tabular-nums">0,00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('return.new_value') }}</span>
                        <span id="t-new" class="tabular-nums">0,00</span>
                    </div>
                    <div id="diff-box" class="flex justify-between border-t pt-2 text-lg font-semibold">
                        <span id="diff-label">{{ __('return.difference') }}</span>
                        <span id="t-diff" class="tabular-nums">0,00</span>
                    </div>
                </div>
            </div>

            <button id="confirm" disabled
                    class="rounded-lg bg-emerald-600 text-white px-8 py-3 font-medium disabled:opacity-40">
                {{ __('return.confirm_exchange') }}
            </button>
        </form>
    @endif
</div>
<template id="new-line-template">
    <tr class="line">
        <td>
            <span class="font-medium name"></span>
            <input type="hidden" class="f-product">
        </td>
        <td class="w-24">
            <input type="number" step="0.001" min="0.001" value="1"
                   class="qty w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td class="w-28">
            <input type="number" step="0.01" min="0"
                   class="price w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td class="num font-medium w-24"><bdi class="total">0,00</bdi></td>
        <td class="mid w-12">
            <button type="button" class="remove text-red-500 text-lg leading-none">&times;</button>
        </td>
    </tr>
</template>
@endsection

@php
    $labels = json_encode([
        'customer_pays' => __('return.customer_pays'),
        'shop_refunds' => __('return.shop_refunds'),
        'difference' => __('return.difference'),
        'no_results' => __('purchase.no_results'),
    ], JSON_UNESCAPED_UNICODE);
@endphp

@push('scripts')
<script>
onAppReady(function () {
    const t = {!! $labels !!};
    const wholesale = @json($sale?->type === 'wholesale');
    let seq = 0;

    const priceOf = (product) =>
        wholesale && money.parse(product.wholesale_price_raw) > 0
            ? product.wholesale_price_raw
            : product.retail_price_raw;

    function recalc() {
        let back = 0;
        let out = 0;

        $('.return-line').each(function () {
            back += money.parse($(this).find('.qty').val()) * money.parse($(this).data('price'));
        });

        $('#new-lines .line').each(function () {
            const total = money.parse($(this).find('.qty').val()) * money.parse($(this).find('.price').val());
            out += total;
            $(this).find('.total').text(money.format(total));
        });

        const diff = out - back;

        $('#t-back').text(money.format(back));
        $('#t-new').text(money.format(out));
        $('#t-diff').text(money.format(Math.abs(diff)));

        // The label says who hands over money, so nobody has to work it out.
        $('#diff-label').text(diff > 0 ? t.customer_pays : diff < 0 ? t.shop_refunds : t.difference);
        $('#diff-box').removeClass('text-amber-700 text-emerald-700')
            .addClass(diff > 0 ? 'text-amber-700' : diff < 0 ? 'text-emerald-700' : '');

        const hasNew = $('#new-lines .line').length > 0;
        $('#new-empty').toggle(!hasNew);
        $('#confirm').prop('disabled', back <= 0 || !hasNew);
    }

    function addLine(product) {
        if (bumpExisting($('#new-lines .line'), product.id)) return recalc();

        const i = seq++;
        const $row = $($('#new-line-template').html());

        $row.find('.name').text(product.name);
        $row.find('.f-product').attr('name', `new_items[${i}][product_id]`).val(product.id);
        $row.find('.qty').attr('name', `new_items[${i}][quantity]`);
        $row.find('.price').attr('name', `new_items[${i}][unit_price]`).val(priceOf(product));

        $('#new-lines').append($row);
        recalc();
    }

    productSearch({
        input: '#scan',
        results: '#scan-results',
        url: @json(route('products.search')),
        emptyText: t.no_results,
        meta: (product) => product.stock,
        onPick: addLine,
    });

    $(document).on('input', '.qty, .price, #extra', recalc);
    $(document).on('click', '.remove', function () { $(this).closest('.line').remove(); recalc(); });

    recalc();
});
</script>
@endpush
