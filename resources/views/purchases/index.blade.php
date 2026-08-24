@extends('layouts.app')
@section('title', __('nav.purchases'))

@section('content')
<div class="space-y-4">
    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <select name="supplier_id" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('purchase.all_suppliers') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 px-3 py-2.5">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 px-3 py-2.5">

        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm">
            <input type="checkbox" name="status" value="due" @checked(request('status') === 'due')
                   class="rounded border-slate-300" onchange="this.form.submit()">
            {{ __('purchase.unpaid_only') }}
        </label>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @can('purchase.create')
            <a href="{{ route('purchases.create') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('purchase.new') }}</a>
        @endcan
    </form>

    <div class="table-card table-scroll">
        <table class="table min-w-[640px]">
            <thead>
                <tr>
                    <th class="num">{{ __('purchase.fields.reference') }}</th>
                    <th>{{ __('purchase.fields.supplier_id') }}</th>
                    <th class="num">{{ __('purchase.fields.purchased_at') }}</th>
                    <th class="num">{{ __('purchase.total') }}</th>
                    <th class="num">{{ __('purchase.due') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr class="cursor-pointer"
                        onclick="window.location='{{ route('purchases.show', $purchase) }}'">
                        <td class="num font-medium">{{ $purchase->reference }}</td>
                        <td>{{ $purchase->supplier->name }}</td>
                        <td class="num text-slate-500">{{ $purchase->purchased_at->format('Y-m-d') }}</td>
                        <td class="num font-medium">{{ money($purchase->total) }}</td>
                        <td class="num">
                            @if ($purchase->due_amount > 0)
                                <span class="text-amber-700 font-medium">{{ money($purchase->due_amount) }}</span>
                            @else
                                <span class="text-emerald-600">{{ __('purchase.paid') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">{{ __('purchase.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $purchases->links() }}
</div>
@endsection
