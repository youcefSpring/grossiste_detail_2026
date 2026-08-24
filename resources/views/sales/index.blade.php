@extends('layouts.app')
@section('title', __('nav.sales'))

@section('content')
<div class="space-y-4">
    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 px-3 py-2.5">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 px-3 py-2.5">

        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm whitespace-nowrap">
            <input type="checkbox" name="status" value="due" @checked(request('status') === 'due')
                   class="rounded border-slate-300" onchange="this.form.submit()">
            {{ __('sale.unpaid_only') }}
        </label>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @can('sale.create')
            <a href="{{ route('sales.create') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('nav.new_sale') }}</a>
        @endcan
    </form>

    <div class="table-card table-scroll">
        <table class="table min-w-[680px]">
            <thead>
                <tr>
                    <th class="num">{{ __('sale.fields.invoice_number') }}</th>
                    <th>{{ __('sale.fields.customer_id') }}</th>
                    <th class="num">{{ __('sale.fields.sold_at') }}</th>
                    <th class="num">{{ __('purchase.total') }}</th>
                    <th class="num">{{ __('purchase.due') }}</th>
                    <th>{{ __('sale.fields.user_id') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr class="cursor-pointer {{ $sale->isVoided() ? 'opacity-50 line-through' : '' }}"
                        onclick="window.location='{{ route('sales.show', $sale) }}'">
                        <td class="num font-medium">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->customer?->name ?? __('sale.walk_in') }}</td>
                        <td class="num text-slate-500">{{ $sale->sold_at->format('Y-m-d H:i') }}</td>
                        <td class="num font-medium">{{ money($sale->total) }}</td>
                        <td class="num">
                            @if ($sale->due_amount > 0)
                                <span class="text-amber-700 font-medium">{{ money($sale->due_amount) }}</span>
                            @else
                                <span class="text-emerald-600">{{ __('purchase.paid') }}</span>
                            @endif
                        </td>
                        <td class="text-slate-500">{{ $sale->user?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">{{ __('sale.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sales->links() }}
</div>
@endsection
