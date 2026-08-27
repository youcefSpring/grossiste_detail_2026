@extends('layouts.app')
@section('title', __('nav.suppliers'))

@section('content')
<div class="space-y-4" data-live-root>
    @if ($totalDebt > 0)
        <div class="rounded-2xl bg-amber-50 p-4 flex items-center justify-between">
            <span class="text-amber-800">{{ __('supplier.total_debt') }}</span>
            <span class="text-xl font-semibold tabular-nums text-amber-900">
                {{ money($totalDebt) }} {{ settings('currency.symbol') }}
            </span>
        </div>
    @endif

    <form method="GET" data-live class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('supplier.search_hint') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-3 py-2.5">

        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm whitespace-nowrap">
            <input type="checkbox" name="status" value="debt" @checked(request('status') === 'debt')
                   class="rounded border-slate-300">
            {{ __('supplier.with_debt') }}
        </label>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @include('partials.per-page')

        @can('supplier.manage')
            <a href="{{ route('suppliers.create') }}" data-modal data-modal-title="{{ __('supplier.add') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('supplier.add') }}</a>
        @endcan
    </form>

    <div class="space-y-2">
        @forelse ($suppliers as $supplier)
            <div class="flex items-center gap-2 bg-white rounded-2xl shadow-sm ps-4 pe-2 hover:bg-slate-50">
                <a href="{{ route('suppliers.show', $supplier) }}"
                   class="flex-1 min-w-0 flex items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ $supplier->name }}</div>
                        <div class="text-sm text-slate-500">
                            {{ $supplier->phone ?: $supplier->company ?: '/' }}
                        </div>
                    </div>
                    <div class="text-end whitespace-nowrap">
                        @if ($supplier->balance > 0)
                            <div class="font-semibold tabular-nums text-amber-700">{{ money($supplier->balance) }}</div>
                            <div class="text-xs text-slate-400">{{ __('supplier.we_owe') }}</div>
                        @else
                            <span class="text-sm text-emerald-600">{{ __('supplier.settled') }}</span>
                        @endif
                    </div>
                </a>

                @can('supplier.manage')
                    <div class="flex shrink-0 gap-1">
                        <x-action icon="edit" :label="__('common.edit')" :href="route('suppliers.edit', $supplier)"
                                  data-modal data-modal-title="{{ __('supplier.edit') }}"
                                  />

                        <x-action icon="delete" :label="__('common.delete')" tone="danger"
                                  data-modal-delete
                                  data-url="{{ route('suppliers.destroy', $supplier) }}"
                                  data-message="{{ __('supplier.delete_confirm') }}"
                                  />
                    </div>
                @endcan
            </div>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('supplier.none') }}</div>
        @endforelse
    </div>

    {{ $suppliers->links() }}
</div>
@endsection
