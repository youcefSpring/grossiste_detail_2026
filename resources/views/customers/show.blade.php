@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<div class="space-y-4">

    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xl font-semibold">
                    {{ $customer->name }}
                    @if ($customer->is_wholesale)
                        <span class="ms-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-600">
                            {{ __('sale.types.wholesale') }}
                        </span>
                    @endif
                </div>
                <div class="text-sm text-slate-500 tabular-nums">{{ $customer->phone ?: '—' }}</div>
            </div>

            <div class="text-end">
                <div class="text-2xl font-semibold tabular-nums {{ $customer->balance > 0 ? 'text-amber-700' : 'text-emerald-600' }}">
                    {{ money($customer->balance) }} <span class="text-sm font-normal">{{ settings('currency.symbol') }}</span>
                </div>
                <div class="text-xs text-slate-400">
                    {{ $customer->balance > 0 ? __('customer.owes_us') : __('supplier.settled') }}
                </div>
                @if ($customer->credit_limit > 0)
                    <div class="text-xs text-slate-400 tabular-nums">
                        {{ __('customer.limit') }} {{ money($customer->credit_limit) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @can('customer.manage')
                <x-action icon="edit" :label="__('common.edit')" :href="route('customers.edit', $customer)" />
            @endcan
            @can('sale.create')
                <a href="{{ route('sales.create') }}" class="rounded-lg border px-4 py-2 text-sm">+ {{ __('nav.new_sale') }}</a>
            @endcan
        </div>
    </div>

    @can('payment.record')
        @if ($customer->balance > 0)
            <form method="POST" action="{{ route('customers.collect', $customer) }}"
                  class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
                @csrf
                <h2 class="font-medium">{{ __('customer.collect') }}</h2>

                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="block space-y-1">
                        <span class="text-sm text-slate-600">{{ __('supplier.fields.amount') }}</span>
                        <input name="amount" type="number" step="0.01" min="0.01" required
                               max="{{ number_format($customer->balance / 100, 2, '.', '') }}"
                               value="{{ number_format($customer->balance / 100, 2, '.', '') }}"
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

    <div class="grid gap-4 xl:grid-cols-2 items-start">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-medium mb-3">{{ __('customer.sales') }}</h2>
        <div class="divide-y">
            @forelse ($sales as $sale)
                <a href="{{ route('sales.show', $sale) }}"
                   class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50 {{ $sale->isVoided() ? 'opacity-50 line-through' : '' }}">
                    <div>
                        <div class="font-medium tabular-nums">{{ $sale->invoice_number }}</div>
                        <div class="text-xs text-slate-400 tabular-nums">{{ $sale->sold_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="text-end whitespace-nowrap">
                        <div class="tabular-nums font-medium">{{ money($sale->total) }}</div>
                        @if ($sale->due_amount > 0)
                            <div class="text-xs text-amber-700 tabular-nums">
                                {{ __('purchase.due') }} {{ money($sale->due_amount) }}
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('sale.none') }}</p>
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
                            @if ($payment->payable) · {{ $payment->payable->invoice_number }} @endif
                        </div>
                    </div>
                    <div class="tabular-nums font-medium text-emerald-700">+ {{ money($payment->amount) }}</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">{{ __('payment.none') }}</p>
            @endforelse
        </div>
    </div>
    </div>
</div>
@endsection
