@extends('layouts.app')
@section('title', __('purchase.new'))

@section('content')
<form method="POST" action="{{ route('purchases.store') }}" id="purchase-form" class="space-y-4">
    @csrf

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- Step 1: who and when --}}
    <div class="bg-white rounded-2xl shadow-sm p-4 grid gap-4 sm:grid-cols-2">
        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('purchase.fields.supplier_id') }}</span>
            <select name="supplier_id" class="w-full rounded-lg border-slate-300 py-2.5">
                <option value="">{{ __('purchase.no_supplier') }}</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('purchase.fields.purchased_at') }}</span>
            <input type="date" name="purchased_at" required
                   value="{{ old('purchased_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>
    </div>

    {{-- Step 2: scan or search, line lands in the table --}}
    <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        @include('partials.product-search', ['placeholder' => __('purchase.search_hint'), 'big' => false])

        <div class="table-card table-scroll">
            <table class="table table-edit table-stack md:min-w-[560px]">
                <thead>
                    <tr>
                        <th>{{ __('product.fields.name') }}</th>
                        <th class="num w-32">{{ __('purchase.fields.quantity') }}</th>
                        <th class="num w-36">{{ __('purchase.fields.unit_cost') }}</th>
                        <th class="num w-32">{{ __('purchase.line_total') }}</th>
                        <th class="w-12"></th>
                    </tr>
                </thead>
                <tbody id="lines"></tbody>
            </table>
        </div>

        <p id="empty-lines" class="table-empty">{{ __('purchase.no_lines') }}</p>
    </div>

    {{-- Step 3: money --}}
    <div class="bg-white rounded-2xl shadow-sm p-4 grid gap-4 lg:grid-cols-2">
        <div class="space-y-3">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('purchase.fields.discount_amount') }}</span>
                <input id="discount" name="discount_amount" type="number" step="0.01" min="0"
                       value="{{ old('discount_amount', 0) }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('purchase.fields.paid_amount') }}</span>
                <input id="paid" name="paid_amount" type="number" step="0.01" min="0"
                       value="{{ old('paid_amount', 0) }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums text-lg">
                <button type="button" id="pay-all" class="text-xs text-emerald-700 hover:underline">
                    {{ __('purchase.pay_all') }}
                </button>
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('purchase.fields.method') }}</span>
                <select name="method" class="w-full rounded-lg border-slate-300 py-2.5">
                    @foreach (settings('payment.methods', ['cash']) as $method)
                        <option value="{{ $method }}" @selected(old('method', settings('sale.default_payment_method')) === $method)>
                            {{ __('payment.methods.'.$method) }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 space-y-2 text-sm self-start">
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.subtotal') }}</span>
                <span id="t-subtotal" class="tabular-nums">0,00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.fields.discount_amount') }}</span>
                <span id="t-discount" class="tabular-nums">0,00</span>
            </div>
            <div class="flex justify-between border-t pt-2 text-lg font-semibold">
                <span>{{ __('purchase.total') }}</span>
                <span id="t-total" class="tabular-nums">0,00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">{{ __('purchase.fields.paid_amount') }}</span>
                <span id="t-paid" class="tabular-nums">0,00</span>
            </div>
            <div class="flex justify-between text-amber-700 font-medium">
                <span>{{ __('purchase.due') }}</span>
                <span id="t-due" class="tabular-nums">0,00</span>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button id="submit-btn" disabled
                class="rounded-lg bg-emerald-600 text-white px-8 py-3 font-medium disabled:opacity-40">
            {{ __('purchase.confirm') }}
        </button>
        <a href="{{ route('purchases.index') }}" class="rounded-lg border px-6 py-3">{{ __('app.cancel') }}</a>
    </div>
</form>

{{-- One line of the table, cloned by jQuery --}}
<template id="line-template">
    <tr class="line">
        <td>
            <div class="font-medium name"></div>
            <input type="hidden" class="f-product">
        </td>
        <td class="p-2" data-label="{{ __('purchase.fields.quantity') }}">
            <input type="number" step="1" min="0" value="1"
                   class="qty w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td class="p-2" data-label="{{ __('purchase.fields.unit_cost') }}">
            <input type="number" step="0.01" min="0"
                   class="cost w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
        </td>
        <td class="num font-medium" data-label="{{ __('purchase.line_total') }}"><bdi class="total">0,00</bdi></td>
        <td class="mid">
            <button type="button" class="remove text-red-500 text-lg leading-none">&times;</button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
onAppReady(function () {
    let seq = 0;

    function recalc() {
        let subtotal = 0;

        $('#lines .line').each(function () {
            const total = money.parse($(this).find('.qty').val()) * money.parse($(this).find('.cost').val());
            subtotal += total;
            $(this).find('.total').text(money.format(total));
        });

        const discount = Math.min(money.parse($('#discount').val()), subtotal);
        const total = subtotal - discount;
        const paid = Math.min(money.parse($('#paid').val()), total);

        $('#t-subtotal').text(money.format(subtotal));
        $('#t-discount').text(money.format(discount));
        $('#t-total').text(money.format(total));
        $('#t-paid').text(money.format(paid));
        $('#t-due').text(money.format(total - paid));

        const hasLines = $('#lines .line').length > 0;
        $('#empty-lines').toggle(!hasLines);
        $('#submit-btn').prop('disabled', !hasLines);
        $('#pay-all').data('total', total);
    }

    function addLine(product) {
        if (bumpExisting($('#lines .line'), product.id)) return recalc();

        const i = seq++;
        const $row = $($('#line-template').html());

        $row.find('.name').text(product.name);
        $row.find('.f-product').attr('name', `items[${i}][product_id]`).val(product.id);
        $row.find('.qty').attr('step', product.quantity_step || '0.001').attr('name', `items[${i}][quantity]`);
        $row.find('.cost').attr('name', `items[${i}][unit_cost]`).val(product.cost_price_raw ?? '');

        $('#lines').append($row);
        $row.find('.cost').trigger('focus').trigger('select');
        recalc();
    }

    productSearch({
        input: '#scan',
        results: '#scan-results',
        url: @json(route('products.search')),
        emptyText: @json(__('purchase.no_results')),
        meta: (product) => product.stock,
        onPick: addLine,
    });

    $(document).on('input', '.qty, .cost, #discount, #paid', recalc);
    $(document).on('click', '.remove', function () { $(this).closest('.line').remove(); recalc(); });
    $('#pay-all').on('click', function () { $('#paid').val($(this).data('total').toFixed(2)); recalc(); });

    recalc();
});
</script>
@endpush
