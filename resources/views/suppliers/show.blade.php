@extends('layouts.app')
@section('title', $supplier->name)

@section('content')
<div class="space-y-4">

    {{-- What matters: how much we still owe --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xl font-semibold">{{ $supplier->name }}</div>
                <div class="text-sm text-slate-500">
                    @if ($supplier->phone)<span class="tabular-nums">{{ $supplier->phone }}</span>@endif
                    @if ($supplier->company) · {{ $supplier->company }} @endif
                </div>
            </div>

            <div class="text-end">
                <div class="text-2xl font-semibold tabular-nums {{ $supplier->balance > 0 ? 'text-amber-700' : 'text-emerald-600' }}">
                    {{ money($supplier->balance) }} <span class="text-sm font-normal">{{ settings('currency.symbol') }}</span>
                </div>
                <div class="text-xs text-slate-400">
                    {{ $supplier->balance > 0 ? __('supplier.we_owe') : __('supplier.settled') }}
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @can('supplier.manage')
                <x-action icon="edit" :label="__('common.edit')" :href="route('suppliers.edit', $supplier)" />
            @endcan
            @can('purchase.create')
                <a href="{{ route('purchases.create') }}" class="rounded-lg border px-4 py-2 text-sm">
                    + {{ __('purchase.new') }}
                </a>
            @endcan
        </div>
    </div>

    {{-- Pay them --}}
    @can('payment.record')
        @if ($supplier->balance > 0)
            <form method="POST" action="{{ route('suppliers.pay', $supplier) }}"
                  class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
                @csrf
                <h2 class="font-medium">{{ __('supplier.pay') }}</h2>

                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="block space-y-1">
                        <span class="text-sm text-slate-600">{{ __('supplier.fields.amount') }}</span>
                        <input name="amount" type="number" step="0.01" min="0.01" required
                               max="{{ number_format($supplier->balance / 100, 2, '.', '') }}"
                               value="{{ number_format($supplier->balance / 100, 2, '.', '') }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums text-lg">
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-600">{{ __('purchase.fields.method') }}</span>
                        <select name="method" class="w-full rounded-lg border-slate-300 py-2.5">
                            @foreach (settings('payment.methods', ['cash']) as $method)
                                <option value="{{ $method }}">{{ __('payment.methods.'.$method) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-600">{{ __('payment.date') }}</span>
                        <input type="date" name="paid_at" required value="{{ now()->toDateString() }}"
                               max="{{ now()->toDateString() }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    </label>
                </div>

                <button class="rounded-lg bg-emerald-600 text-white px-6 py-3 font-medium">{{ __('app.confirm') }}</button>
            </form>
        @endif
    @endcan

    {{-- Statement --}}
    <div class="grid gap-4 xl:grid-cols-2 items-start">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-medium mb-3">{{ __('supplier.purchases') }}</h2>
        <div class="divide-y">
            @forelse ($purchases as $purchase)
                <a href="{{ route('purchases.show', $purchase) }}"
                   class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50">
                    <div>
                        <div class="font-medium tabular-nums">{{ $purchase->reference }}</div>
                        <div class="text-xs text-slate-400 tabular-nums">{{ $purchase->purchased_at->format('Y-m-d') }}</div>
                    </div>
                    <div class="text-end whitespace-nowrap">
                        <div class="tabular-nums font-medium">{{ money($purchase->total) }}</div>
                        @if ($purchase->due_amount > 0)
                            <div class="text-xs text-amber-700 tabular-nums">
                                {{ __('purchase.due') }} {{ money($purchase->due_amount) }}
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('purchase.none') }}</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-medium mb-3">{{ __('supplier.payments') }}</h2>
        <div class="divide-y">
            @forelse ($payments as $payment)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <div class="text-sm">{{ __('payment.methods.'.$payment->method) }}</div>
                        <div class="text-xs text-slate-400 tabular-nums">
                            {{ $payment->paid_at->format('Y-m-d') }}
                            @if ($payment->payable) · {{ $payment->payable->reference }} @endif
                        </div>
                    </div>
                    <div class="tabular-nums font-medium text-emerald-700">− {{ money($payment->amount) }}</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('payment.none') }}</p>
            @endforelse
        </div>
    </div>
    </div>
</div>
@endsection
