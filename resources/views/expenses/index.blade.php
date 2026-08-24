@extends('layouts.app')
@section('title', __('nav.expenses'))

@section('content')
<div class="space-y-4">
    <div class="rounded-2xl bg-slate-900 text-white p-5 flex items-center justify-between">
        <div>
            <div class="text-sm opacity-70">{{ __('expense.total_for_period') }}</div>
            <div class="text-xs opacity-50 tabular-nums">{{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}</div>
        </div>
        <div class="text-2xl font-semibold tabular-nums">
            {{ money($total) }} <span class="text-sm font-normal opacity-70">{{ settings('currency.symbol') }}</span>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="rounded-lg border-slate-300 px-3 py-2.5">
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="rounded-lg border-slate-300 px-3 py-2.5">

        <select name="expense_category_id" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('expense.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('expense_category_id') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @can('expense.manage')
            <a href="{{ route('expenses.create') }}"
               class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('expense.add') }}</a>
        @endcan
    </form>

    <div class="space-y-2">
        @forelse ($expenses as $expense)
            <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-medium">{{ $expense->category?->name ?? '—' }}</div>
                    <div class="text-sm text-slate-500 truncate">{{ $expense->description ?: '—' }}</div>
                    <div class="text-xs text-slate-400 tabular-nums">
                        {{ $expense->spent_at->format('Y-m-d') }} · {{ $expense->user?->name }}
                    </div>
                </div>
                <div class="text-end whitespace-nowrap">
                    <div class="font-semibold tabular-nums">{{ money($expense->amount) }}</div>
                    @can('expense.manage')
                        <x-action icon="edit" :label="__('common.edit')"
                                  :href="route('expenses.edit', $expense)" class="w-8 h-8" />
                    @endcan
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('expense.none') }}</div>
        @endforelse
    </div>

    {{ $expenses->links() }}
</div>
@endsection
