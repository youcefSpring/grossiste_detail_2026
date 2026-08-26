@extends(modal_layout())
@section('title', $supplier->exists ? __('supplier.edit') : __('supplier.add'))

@section('content')
<form method="POST" class="space-y-4"
      action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
    @csrf
    @if ($supplier->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 items-start xl:grid-cols-[minmax(0,1fr)_380px]">

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('supplier.fields.name') }} <span class="text-red-500">*</span></span>
                    <input name="name" value="{{ old('name', $supplier->name) }}" required autofocus
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('supplier.fields.phone') }}</span>
                    <input name="phone" value="{{ old('phone', $supplier->phone) }}" inputmode="tel"
                           placeholder="0X XX XX XX XX"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums text-lg">
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('supplier.fields.company') }}</span>
                    <input name="company" value="{{ old('company', $supplier->company) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('supplier.fields.address') }}</span>
                    <input name="address" value="{{ old('address', $supplier->address) }}"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                </label>
            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('supplier.fields.note') }}</span>
                <textarea name="note" rows="2"
                          class="w-full rounded-lg border-slate-300 px-3 py-2.5">{{ old('note', $supplier->note) }}</textarea>
            </label>
        </div>

        <div class="space-y-4 xl:sticky xl:top-4">
            <details class="bg-white rounded-2xl shadow-sm" @if ($errors->has('tax_number')) open @endif>
                <summary class="cursor-pointer select-none p-5 font-medium text-slate-700">
                    {{ __('supplier.advanced') }}
                </summary>

                <div class="border-t p-5 space-y-4">
                    <label class="block space-y-1">
                        <span class="text-sm font-medium">{{ __('supplier.fields.tax_number') }}</span>
                        <input name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}"
                               class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                        <span class="text-xs text-slate-400">{{ __('supplier.tax_hint') }}</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                               @checked(old('is_active', $supplier->is_active ?? true))>
                        <span class="text-sm">{{ __('supplier.fields.is_active') }}</span>
                    </label>
                </div>
            </details>

            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
                <a href="{{ route('suppliers.index') }}" data-modal-close
                   class="block text-center rounded-lg border py-3">{{ __('app.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection
