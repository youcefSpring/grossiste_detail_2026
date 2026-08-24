@extends('layouts.app')
@section('title', $expense->exists ? __('expense.edit') : __('expense.add'))

@section('content')
<form method="POST" enctype="multipart/form-data" class="space-y-4"
      action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}">
    @csrf
    @if ($expense->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 items-start xl:grid-cols-[minmax(0,1fr)_380px]">

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('expense.fields.amount') }} <span class="text-red-500">*</span></span>
                <div class="relative">
                    <input name="amount" type="number" step="0.01" min="0.01" required autofocus
                           value="{{ old('amount', $expense->amount ? number_format($expense->amount / 100, 2, '.', '') : '') }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-3 pe-16 text-2xl tabular-nums text-end">
                    <span class="absolute inset-y-0 end-4 flex items-center text-sm text-slate-400">
                        {{ settings('currency.symbol') }}
                    </span>
                </div>
            </label>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('expense.fields.expense_category_id') }}</span>
                    <select name="expense_category_id" class="w-full rounded-lg border-slate-300 py-2.5">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('expense.fields.spent_at') }}</span>
                    <input type="date" name="spent_at" required max="{{ now()->toDateString() }}"
                           value="{{ old('spent_at', $expense->spent_at?->format('Y-m-d') ?? now()->toDateString()) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('purchase.fields.method') }}</span>
                    <select name="method" class="w-full rounded-lg border-slate-300 py-2.5">
                        @foreach (settings('payment.methods', ['cash']) as $method)
                            <option value="{{ $method }}" @selected(old('method', $expense->method) === $method)>
                                {{ __('payment.methods.'.$method) }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('expense.fields.description') }}</span>
                <input name="description" value="{{ old('description', $expense->description) }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>
        </div>

        <div class="space-y-4 xl:sticky xl:top-4">
            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
                <span class="text-sm font-medium">{{ __('expense.fields.attachment') }}</span>
                <input type="file" name="attachment" accept="image/*,application/pdf"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @if ($expense->attachment_path)
                    <a href="{{ asset('storage/'.$expense->attachment_path) }}" target="_blank"
                       class="block text-sm text-slate-500 hover:underline">{{ __('expense.current_attachment') }}</a>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
                <a href="{{ route('expenses.index') }}"
                   class="block text-center rounded-lg border py-3">{{ __('app.cancel') }}</a>

                @if ($expense->exists)
                    <button form="delete-expense" data-confirm="{{ __('expense.delete_confirm') }}"
                            class="w-full rounded-lg text-red-600 py-2 text-sm">{{ __('common.delete') }}</button>
                @endif
            </div>
        </div>
    </div>
</form>

@if ($expense->exists)
    <form id="delete-expense" method="POST" action="{{ route('expenses.destroy', $expense) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
@endif
@endsection
