{{-- Shared invoice lookup box for returns and exchanges --}}
<form method="GET" class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap gap-2">
    <input type="search" name="invoice" value="{{ request('invoice') }}" autofocus
           placeholder="{{ __('return.invoice_hint') }}"
           class="flex-1 min-w-48 rounded-lg border-slate-300 px-4 py-3 text-lg tabular-nums">
    <button class="rounded-lg bg-slate-900 text-white px-6 py-3">{{ __('return.find') }}</button>
</form>

@if ($searched && ! $sale)
    <div class="rounded-2xl bg-red-50 p-6 text-center text-red-700">{{ __('return.not_found') }}</div>
@endif
