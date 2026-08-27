@extends('layouts.app')
@section('title', __('nav.new_sale'))

@section('content')
<form method="POST" action="{{ route('sales.store') }}" id="pos-form">
    @csrf

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1 mb-4">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1fr_380px] items-start">

        {{-- LEFT: scan and cart --}}
        <div class="space-y-3">
            @include('partials.product-search')

            {{-- What the shop actually sells: one tap, no typing --}}
            @if ($quickPicks->isNotEmpty())
                <div class="space-y-2">
                    <div class="text-xs font-medium text-slate-400">{{ __('sale.quick_picks') }}</div>

                    <div class="grid gap-2 grid-cols-2 sm:grid-cols-3 2xl:grid-cols-4">
                        @foreach ($quickPicks as $product)
                            @php
                                $payload = [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'stock' => (float) $product->stock,
                                    'quantity_step' => $product->quantityStep(),
                                    'retail_price_raw' => number_format($product->retail_price / 100, 2, '.', ''),
                                    'wholesale_price_raw' => number_format($product->wholesale_price / 100, 2, '.', ''),
                                ];
                            @endphp

                            <button type="button" class="quick-pick rounded-xl bg-white p-3 text-start shadow-sm
                                                         hover:bg-emerald-50 active:scale-[0.98] transition"
                                    data-product="{{ json_encode($payload, JSON_UNESCAPED_UNICODE) }}">
                                <span class="block font-medium text-sm line-clamp-2 min-h-10">{{ $product->name }}</span>
                                <span class="mt-1 flex items-baseline justify-between gap-2">
                                    <span class="tabular-nums font-semibold">{{ money($product->retail_price) }}</span>
                                    <span class="text-xs text-slate-400 tabular-nums">{{ (float) $product->stock }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="table-card table-scroll">
                <table class="table table-edit table-stack md:min-w-[520px]">
                    <thead>
                        <tr>
                            <th>{{ __('product.fields.name') }}</th>
                            <th class="num w-28">{{ __('sale.fields.quantity') }}</th>
                            <th class="num w-32">{{ __('sale.fields.unit_price') }}</th>
                            <th class="num w-28">{{ __('purchase.line_total') }}</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="cart"></tbody>
                </table>
                <p id="cart-empty" class="table-empty">{{ __('sale.cart_empty') }}</p>
            </div>
        </div>

        {{-- RIGHT: who pays, how much, confirm --}}
        <div class="bg-white rounded-2xl shadow-sm p-4 space-y-4 lg:sticky lg:top-4">

            <div class="flex gap-2">
                @foreach (['retail', 'wholesale'] as $type)
                    <label class="flex-1">
                        <input type="radio" name="type" value="{{ $type }}" class="peer sr-only"
                               @checked(old('type', settings('sale.default_type', 'retail')) === $type)>
                        <span class="block text-center rounded-lg border py-2.5 text-sm cursor-pointer
                                     peer-checked:bg-slate-900 peer-checked:text-white peer-checked:border-slate-900">
                            {{ __('sale.types.'.$type) }}
                        </span>
                    </label>
                @endforeach
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('sale.fields.customer_id') }}</span>
                <select id="customer" name="customer_id" class="w-full rounded-lg border-slate-300 py-2.5">
                    <option value="">{{ __('sale.walk_in') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                                data-wholesale="{{ $customer->is_wholesale ? 1 : 0 }}"
                                data-balance="{{ money($customer->balance) }}">
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                <span id="customer-debt" class="text-xs text-amber-700 hidden"></span>
            </label>

            <div class="rounded-xl bg-slate-50 p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">{{ __('purchase.subtotal') }}</span>
                    <span id="t-subtotal" class="tabular-nums">0,00</span>
                </div>

                <label class="flex items-center justify-between gap-2 text-sm">
                    <span class="text-slate-500">{{ __('sale.fields.discount_amount') }}</span>
                    <input id="discount" name="discount_amount" type="number" step="0.01" min="0" value="0"
                           class="w-28 rounded-lg border-slate-300 px-2 py-1.5 text-end tabular-nums">
                </label>

                <div class="flex justify-between border-t pt-2 text-2xl font-semibold">
                    <span>{{ __('purchase.total') }}</span>
                    <span id="t-total" class="tabular-nums">0,00</span>
                </div>
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('sale.fields.paid_amount') }}</span>
                <input id="paid" name="paid_amount" type="number" step="0.01" min="0" value="0"
                       class="w-full rounded-lg border-slate-300 px-3 py-3 text-xl tabular-nums text-end">
                <div class="flex gap-2 pt-1">
                    <button type="button" id="pay-exact"
                            class="flex-1 rounded-lg border py-2 text-sm">{{ __('sale.exact') }}</button>
                    <button type="button" id="pay-none"
                            class="flex-1 rounded-lg border py-2 text-sm">{{ __('sale.on_credit') }}</button>
                </div>
            </label>

            <div id="change-box" class="hidden rounded-xl bg-emerald-50 p-3 flex justify-between font-semibold text-emerald-800">
                <span>{{ __('sale.change') }}</span>
                <span id="t-change" class="tabular-nums">0,00</span>
            </div>

            <div id="due-box" class="hidden rounded-xl bg-amber-50 p-3 flex justify-between font-semibold text-amber-800">
                <span>{{ __('purchase.due') }}</span>
                <span id="t-due" class="tabular-nums">0,00</span>
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('purchase.fields.method') }}</span>
                <select name="method" class="w-full rounded-lg border-slate-300 py-2.5">
                    @foreach (settings('payment.methods', ['cash']) as $method)
                        <option value="{{ $method }}" @selected(settings('sale.default_payment_method') === $method)>
                            {{ __('payment.methods.'.$method) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button id="confirm" disabled
                    class="sticky bottom-2 lg:static z-10 w-full rounded-xl bg-emerald-600 text-white py-4 text-lg font-semibold shadow-lg lg:shadow-none disabled:opacity-40">
                {{ __('sale.confirm') }}
                <span class="text-xs font-normal opacity-75">F9</span>
            </button>
        </div>
    </div>
</form>

<template id="cart-row">
    <tr class="line">
        <td>
            <div class="font-medium name"></div>
            <div class="text-xs text-slate-400 stock"></div>
            <input type="hidden" class="f-product">
        </td>
        <td data-label="{{ __('sale.fields.quantity') }}">
            <input type="number" step="1" min="0" value="1"
                   class="qty w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td data-label="{{ __('sale.fields.unit_price') }}">
            <input type="number" step="0.01" min="0"
                   class="price w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td class="num font-medium" data-label="{{ __('purchase.line_total') }}"><bdi class="total">0,00</bdi></td>
        <td class="mid">
            <button type="button" class="remove text-red-500 text-xl leading-none">&times;</button>
        </td>
    </tr>
</template>
@endsection

@php
    $labels = json_encode([
        'stock' => __('product.fields.stock'),
        'no_results' => __('purchase.no_results'),
    ], JSON_UNESCAPED_UNICODE);
@endphp

@push('scripts')
<script>
onAppReady(function () {
    const t = {!! $labels !!};
    let seq = 0;

    const isWholesale = () => $('input[name=type]:checked').val() === 'wholesale';
    const priceOf = (product) =>
        isWholesale() && money.parse(product.wholesale_price_raw) > 0
            ? product.wholesale_price_raw
            : product.retail_price_raw;

    function recalc() {
        let subtotal = 0;

        $('#cart .line').each(function () {
            const total = money.parse($(this).find('.qty').val()) * money.parse($(this).find('.price').val());
            subtotal += total;
            $(this).find('.total').text(money.format(total));
        });

        const discount = Math.min(money.parse($('#discount').val()), subtotal);
        const total = subtotal - discount;
        const paid = money.parse($('#paid').val());

        $('#t-subtotal').text(money.format(subtotal));
        $('#t-total').text(money.format(total));

        // Change due and unpaid balance: the two numbers a cashier reads out loud.
        $('#change-box').toggleClass('hidden', paid <= total);
        $('#t-change').text(money.format(Math.max(0, paid - total)));
        $('#due-box').toggleClass('hidden', paid >= total);
        $('#t-due').text(money.format(Math.max(0, total - paid)));

        const hasLines = $('#cart .line').length > 0;
        $('#cart-empty').toggle(!hasLines);
        $('#confirm').prop('disabled', !hasLines);
        $('#pay-exact').data('total', total);
    }

    function addLine(product) {
        if (bumpExisting($('#cart .line'), product.id)) return recalc();

        const i = seq++;
        const $row = $($('#cart-row').html());

        $row.find('.name').text(product.name);
        $row.find('.stock').text(`${t.stock}: ${product.stock}`);
        $row.find('.f-product').attr('name', `items[${i}][product_id]`).val(product.id);
        $row.find('.qty')
            .attr('name', `items[${i}][quantity]`)
            .attr('step', product.quantity_step || '0.001');
        $row.find('.price').attr('name', `items[${i}][unit_price]`).val(priceOf(product));
        $row.data('product', product);

        $('#cart').append($row);
        recalc();
    }

    productSearch({
        input: '#scan',
        results: '#scan-results',
        url: @json(route('products.search')),
        emptyText: t.no_results,
        meta: (product) => `${priceOf(product)} · ${product.stock}`,
        onPick: addLine,
    });

    // Switching retail/wholesale reprices everything already in the cart.
    $('input[name=type]').on('change', function () {
        $('#cart .line').each(function () {
            $(this).find('.price').val(priceOf($(this).data('product')));
        });
        recalc();
    });

    // A wholesale customer flips the price mode by itself.
    $('#customer').on('change', function () {
        const $option = $(this).find('option:selected');
        const balance = $option.data('balance');

        if ($option.data('wholesale')) {
            $('input[name=type][value=wholesale]').prop('checked', true).trigger('change');
        }

        $('#customer-debt')
            .toggleClass('hidden', !$(this).val() || balance === '0,00')
            .text(@json(__('sale.customer_owes')) + ' ' + balance);
    });

    $(document).on('click', '.quick-pick', function () {
        addLine($(this).data('product'));
    });

    $(document).on('input', '.qty, .price, #discount, #paid', recalc);
    $(document).on('click', '.remove', function () { $(this).closest('.line').remove(); recalc(); });

    $('#pay-exact').on('click', function () { $('#paid').val($(this).data('total').toFixed(2)); recalc(); });
    $('#pay-none').on('click', function () { $('#paid').val(0); recalc(); });

    // The till should never need a mouse.
    $(document).on('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); $('#scan').trigger('focus').trigger('select'); }
        if (e.key === 'F4') { e.preventDefault(); $('#customer').trigger('focus'); }
        if (e.key === 'F9' && !$('#confirm').prop('disabled')) { e.preventDefault(); $('#pos-form').trigger('submit'); }
    });

    $('#scan').trigger('focus');
    recalc();
});
</script>
@endpush
