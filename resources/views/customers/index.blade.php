@extends('layouts.app')
@section('title', __('nav.customers'))

@section('content')
<div class="space-y-4">
    @if ($totalDebt > 0)
        <div class="rounded-2xl bg-amber-50 p-4 flex items-center justify-between">
            <span class="text-amber-800">{{ __('customer.total_debt') }}</span>
            <span class="text-xl font-semibold tabular-nums text-amber-900">
                {{ money($totalDebt) }} {{ settings('currency.symbol') }}
            </span>
        </div>
    @endif

    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('customer.search_hint') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-3 py-2.5">

        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm whitespace-nowrap">
            <input type="checkbox" name="status" value="debt" @checked(request('status') === 'debt')
                   class="rounded border-slate-300" onchange="this.form.submit()">
            {{ __('customer.with_debt') }}
        </label>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @can('customer.manage')
            <a href="{{ route('customers.create') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('customer.add') }}</a>
        @endcan
    </form>

    <div class="space-y-2">
        @forelse ($customers as $customer)
            <a href="{{ route('customers.show', $customer) }}"
               class="flex items-center justify-between gap-3 bg-white rounded-2xl shadow-sm p-4 hover:bg-slate-50">
                <div class="min-w-0">
                    <div class="font-medium truncate">
                        {{ $customer->name }}
                        @if ($customer->is_wholesale)
                            <span class="ms-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-600">
                                {{ __('sale.types.wholesale') }}
                            </span>
                        @endif
                    </div>
                    <div class="text-sm text-slate-500 tabular-nums">{{ $customer->phone ?: '—' }}</div>
                </div>
                <div class="text-end whitespace-nowrap">
                    @if ($customer->balance > 0)
                        <div class="font-semibold tabular-nums text-amber-700">{{ money($customer->balance) }}</div>
                        <div class="text-xs text-slate-400">{{ __('customer.owes_us') }}</div>
                    @else
                        <span class="text-sm text-emerald-600">{{ __('supplier.settled') }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('customer.none') }}</div>
        @endforelse
    </div>

    {{ $customers->links() }}
</div>
@endsection
