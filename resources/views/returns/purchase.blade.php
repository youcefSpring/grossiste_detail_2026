@extends('layouts.app')
@section('title', __('return.to_supplier_title'))

@section('content')
<div class="space-y-4">

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('purchase-returns.store', $purchase) }}" class="space-y-4">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="font-semibold tabular-nums">{{ $purchase->reference }}</div>
                <div class="text-sm text-slate-500">
                    {{ $purchase->supplier->name }} · {{ $purchase->purchased_at->format('Y-m-d') }}
                </div>
            </div>
            <div class="tabular-nums font-medium">{{ money($purchase->total) }} {{ settings('currency.symbol') }}</div>
        </div>

        <div class="table-card table-scroll">
            <table class="table table-edit min-w-[520px]">
                <thead>
                    <tr>
                        <th>{{ __('product.fields.name') }}</th>
                        <th class="num">{{ __('return.bought') }}</th>
                        <th class="num">{{ __('return.returnable') }}</th>
                        <th class="num w-32">{{ __('return.returning') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $i => $item)
                        @php($left = (float) $item->quantity - (float) $item->returned_quantity)
                        <tr class="return-line {{ $left <= 0 ? 'opacity-40' : '' }}" data-price="{{ $item->unit_cost / 100 }}">
                            <td>
                                <div class="font-medium">{{ $item->product_name }}</div>
                                <div class="text-xs text-slate-400 tabular-nums">{{ money($item->unit_cost) }}</div>
                                <input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}">
                            </td>
                            <td class="num text-slate-500">{{ (float) $item->quantity }}</td>
                            <td class="num font-medium">{{ $left }}</td>
                            <td>
                                <input type="number" name="items[{{ $i }}][quantity]" step="0.001" min="0" max="{{ $left }}"
                                       value="0" {{ $left <= 0 ? 'disabled' : '' }}
                                       class="qty w-full rounded-lg border-slate-300 px-2 py-2 text-end tabular-nums">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-4 grid gap-4 sm:grid-cols-2">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('return.reason') }}</span>
                <input name="reason" class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>

            <div class="rounded-xl bg-slate-50 p-4 self-start">
                <div class="text-sm text-slate-500">{{ __('return.credit_from_supplier') }}</div>
                <div id="t-total" class="mt-1 text-2xl font-semibold tabular-nums">0,00</div>
            </div>
        </div>

        <div class="flex gap-2">
            <button id="confirm" disabled
                    class="rounded-lg bg-emerald-600 text-white px-8 py-3 font-medium disabled:opacity-40">
                {{ __('return.confirm') }}
            </button>
            <a href="{{ route('purchases.show', $purchase) }}" class="rounded-lg border px-6 py-3">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
onAppReady(function () {
    const num = (v) => parseFloat(String(v).replace(',', '.')) || 0;
    const fmt = (v) => v.toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalc() {
        let total = 0;
        $('.return-line').each(function () {
            total += num($(this).find('.qty').val()) * num($(this).data('price'));
        });
        $('#t-total').text(fmt(total));
        $('#confirm').prop('disabled', total <= 0);
    }

    $(document).on('input', '.qty', recalc);
    recalc();
});
</script>
@endpush
