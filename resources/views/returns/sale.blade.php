@extends('layouts.app')
@section('title', __('return.new'))

@section('content')
<div class="space-y-4">

    @include('returns._lookup')

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if ($sale)
        <form method="POST" action="{{ route('returns.store', $sale) }}" id="return-form" class="space-y-4">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-semibold tabular-nums">{{ $sale->invoice_number }}</div>
                    <div class="text-sm text-slate-500">
                        {{ $sale->customer?->name ?? __('sale.walk_in') }} · {{ $sale->sold_at->format('Y-m-d') }}
                    </div>
                </div>
                <div class="tabular-nums font-medium">{{ money($sale->total) }} {{ settings('currency.symbol') }}</div>
            </div>

            @include('returns._lines')

            <div class="bg-white rounded-2xl shadow-sm p-4 grid gap-4 sm:grid-cols-2">
                <div class="space-y-3">
                    <div class="space-y-2">
                        <span class="text-sm font-medium">{{ __('return.refund_method') }}</span>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['cash', 'credit'] as $method)
                                <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer
                                              has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                                    <input type="radio" name="refund_method" value="{{ $method }}" required
                                           @checked($method === 'cash') class="text-slate-900">
                                    <span class="text-sm">{{ __('return.refunds.'.$method) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-400">{{ __('return.refund_hint') }}</p>
                    </div>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('return.reason') }}</span>
                        <input name="reason" class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    </label>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 self-start">
                    <div class="text-sm text-slate-500">{{ __('return.refund_total') }}</div>
                    <div id="t-refund" class="mt-1 text-3xl font-semibold tabular-nums">0,00</div>
                </div>
            </div>

            <button id="confirm" disabled
                    class="rounded-lg bg-emerald-600 text-white px-8 py-3 font-medium disabled:opacity-40">
                {{ __('return.confirm') }}
            </button>
        </form>
    @endif
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

        $('#t-refund').text(fmt(total));
        $('#confirm').prop('disabled', total <= 0);
    }

    $(document).on('input', '.qty', recalc);
    recalc();
});
</script>
@endpush
