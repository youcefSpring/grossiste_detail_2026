@extends('layouts.app')
@section('title', $customer->exists ? __('customer.edit') : __('customer.add'))

@section('content')
<form method="POST" class="space-y-4"
      action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">
    @csrf
    @if ($customer->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 items-start xl:grid-cols-[minmax(0,1fr)_380px]">

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('customer.fields.name') }} <span class="text-red-500">*</span></span>
                    <input name="name" value="{{ old('name', $customer->name) }}" required autofocus
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('customer.fields.phone') }}</span>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" inputmode="tel"
                           placeholder="0X XX XX XX XX"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums text-lg">
                </label>
            </div>

            <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer
                          has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                <input type="hidden" name="is_wholesale" value="0">
                <input type="checkbox" name="is_wholesale" value="1" class="mt-1 rounded border-slate-300"
                       @checked(old('is_wholesale', $customer->is_wholesale))>
                <span>
                    <span class="block text-sm font-medium">{{ __('customer.fields.is_wholesale') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('customer.wholesale_hint') }}</span>
                </span>
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('customer.fields.address') }}</span>
                <input name="address" value="{{ old('address', $customer->address) }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('customer.fields.note') }}</span>
                <textarea name="note" rows="2"
                          class="w-full rounded-lg border-slate-300 px-3 py-2.5">{{ old('note', $customer->note) }}</textarea>
            </label>
        </div>

        <div class="space-y-4 xl:sticky xl:top-4">
            <details class="bg-white rounded-2xl shadow-sm" @if ($errors->has('credit_limit')) open @endif>
                <summary class="cursor-pointer select-none p-5 font-medium text-slate-700">
                    {{ __('supplier.advanced') }}
                </summary>

                <div class="border-t p-5 space-y-4">
                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('customer.fields.credit_limit') }}</span>
                        <input name="credit_limit" type="number" step="0.01" min="0"
                               value="{{ old('credit_limit', $customer->credit_limit ? number_format($customer->credit_limit / 100, 2, '.', '') : '') }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                        <span class="text-xs text-slate-400">{{ __('customer.credit_hint') }}</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                               @checked(old('is_active', $customer->is_active ?? true))>
                        <span class="text-sm">{{ __('customer.fields.is_active') }}</span>
                    </label>
                </div>
            </details>

            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
                <a href="{{ route('customers.index') }}"
                   class="block text-center rounded-lg border py-3">{{ __('app.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection
