@extends('layouts.app')
@section('title', __('stock.movements'))

@section('content')
<div class="space-y-4" data-live-root>

    @if ($product)
        <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center justify-between">
            <div class="font-medium">{{ $product->name }}</div>
            <a href="{{ route('inventory.movements') }}" class="text-sm text-slate-500 hover:underline">
                {{ __('stock.show_all') }}
            </a>
        </div>
    @endif

    <form method="GET" data-live class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="hidden" name="product_id" value="{{ request('product_id') }}">

        <select name="type" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('stock.all_types') }}</option>
            @foreach (\App\Models\StockMovement::TYPES as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('stock.types.'.$type) }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 px-3 py-2.5">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 px-3 py-2.5">

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>
    </form>

    <div class="table-card table-scroll">
        <table class="table table-stack md:min-w-[640px]">
            <thead>
                <tr>
                    <th class="num">{{ __('stock.date') }}</th>
                    <th>{{ __('product.fields.name') }}</th>
                    <th>{{ __('stock.type') }}</th>
                    <th class="num">{{ __('stock.change') }}</th>
                    <th class="num">{{ __('stock.balance') }}</th>
                    <th>{{ __('stock.by') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td class="num text-slate-500" data-label="{{ __('stock.date') }}">
                            <bdi>{{ $movement->created_at->format('Y-m-d H:i') }}</bdi>
                        </td>
                        <td class="font-medium">{{ $movement->product->name }}</td>
                        <td>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs whitespace-nowrap">
                                {{ __('stock.types.'.$movement->type) }}
                            </span>
                            @if ($movement->reason)
                                <div class="text-xs text-slate-400 mt-1">{{ $movement->reason }}</div>
                            @endif
                        </td>
                        <td class="num font-semibold {{ $movement->quantity > 0 ? 'text-emerald-600' : 'text-red-600' }}"
                            data-label="{{ __('stock.change') }}">
                            <bdi>{{ $movement->quantity > 0 ? '+' : '' }}{{ (float) $movement->quantity }}</bdi>
                        </td>
                        <td class="num text-slate-600" data-label="{{ __('stock.balance') }}"><bdi>{{ (float) $movement->balance_after }}</bdi></td>
                        <td class="text-slate-500" data-label="{{ __('stock.by') }}">{{ $movement->user?->name ?? '/' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">{{ __('stock.no_movements') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movements->links() }}
</div>
@endsection
