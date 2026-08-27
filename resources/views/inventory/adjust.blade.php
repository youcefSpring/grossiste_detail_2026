@extends('layouts.app')
@section('title', __('stock.adjust'))

@section('content')
<form method="POST" action="{{ route('inventory.update', $product) }}" class="space-y-4">
    @csrf @method('PUT')

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-5">
        <div>
            <div class="text-lg font-semibold">{{ $product->name }}</div>
            <div class="text-sm text-slate-500">
                {{ __('stock.current') }}:
                <span class="font-semibold tabular-nums text-slate-900">{{ $current }}</span>
                {{ __('product.units.'.$product->unit) }}
            </div>
        </div>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('stock.real_quantity') }} <span class="text-red-500">*</span></span>
            <input name="quantity" type="number" step="{{ $product->quantityStep() }}" min="0" required autofocus
                   value="{{ old('quantity', $current) }}"
                   class="w-full rounded-lg border-slate-300 px-3 py-3 text-2xl tabular-nums text-center">
            <span class="text-xs text-slate-400">{{ __('stock.real_quantity_hint') }}</span>
        </label>

        <div class="space-y-2">
            <span class="text-sm font-medium">{{ __('stock.reason') }} <span class="text-red-500">*</span></span>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Services\StockService::ADJUST_REASONS as $key => $_)
                    <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer
                                  has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="radio" name="reason" value="{{ $key }}" required
                               @checked(old('reason', 'count') === $key) class="text-slate-900">
                        <span class="text-sm">{{ __('stock.reasons.'.$key) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('product.fields.note') }}</span>
            <input name="note" value="{{ old('note') }}" class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>
    </div>

    <div class="flex gap-2">
        <button class="rounded-lg bg-emerald-600 text-white px-6 py-3 font-medium">{{ __('app.confirm') }}</button>
        <a href="{{ route('inventory.index') }}" class="rounded-lg border px-6 py-3">{{ __('app.cancel') }}</a>
    </div>
</form>
@endsection
