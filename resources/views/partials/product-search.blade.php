{{-- Scan-or-search box. Behaviour lives in resources/js/product-search.js --}}
@props(['id' => 'scan', 'placeholder' => null, 'big' => true])

<div class="relative">
    <input id="{{ $id }}" type="search" autocomplete="off"
           placeholder="{{ $placeholder ?? __('sale.scan_hint') }}"
           class="w-full rounded-2xl border-slate-300 {{ $big ? 'px-5 py-4 text-xl shadow-sm' : 'px-4 py-3 text-lg' }}">

    <div id="{{ $id }}-results"
         class="absolute z-20 mt-1 w-full rounded-xl bg-white shadow-lg border hidden max-h-80 overflow-y-auto"></div>
</div>
