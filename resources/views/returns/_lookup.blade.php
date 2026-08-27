{{-- Shared invoice lookup box for returns and exchanges --}}
<div class="relative" id="invoice-lookup">
    <form method="GET" class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap gap-2">
        <input type="search" name="invoice" value="{{ request('invoice') }}" autofocus autocomplete="off"
               id="invoice-input"
               placeholder="{{ __('return.invoice_hint') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-4 py-3 text-lg tabular-nums">
        <button class="rounded-lg bg-slate-900 text-white px-6 py-3">{{ __('return.find') }}</button>
    </form>

    {{-- The customer is standing at the counter: the invoice they want is
         almost always one of the last few, so offer them before any typing. --}}
    @if ($recent->isNotEmpty())
        <div id="invoice-recent"
             class="absolute inset-x-0 top-full z-30 mt-1 hidden overflow-hidden rounded-2xl border bg-white shadow-lg">
            <div class="border-b bg-slate-50 px-4 py-2 text-xs font-medium text-slate-500">
                {{ __('return.recent_invoices') }}
            </div>

            <div class="max-h-80 overflow-y-auto">
                @foreach ($recent as $invoice)
                    <button type="button"
                            class="invoice-pick flex w-full items-center justify-between gap-3 px-4 py-3 text-start hover:bg-emerald-50"
                            data-invoice="{{ $invoice->invoice_number }}"
                            data-search="{{ Str::lower($invoice->invoice_number.' '.($invoice->customer?->name ?? '')) }}">
                        <span class="min-w-0">
                            <span class="block truncate font-medium tabular-nums">{{ $invoice->invoice_number }}</span>
                            <span class="block truncate text-xs text-slate-500">
                                {{ $invoice->customer?->name ?? __('sale.walk_in') }}
                                · {{ $invoice->sold_at->format('Y-m-d H:i') }}
                            </span>
                        </span>
                        <span class="whitespace-nowrap tabular-nums font-medium"><bdi>{{ money($invoice->total) }}</bdi></span>
                    </button>
                @endforeach
            </div>

            <p id="invoice-no-match" class="hidden px-4 py-6 text-center text-sm text-slate-500">
                {{ __('return.no_recent_match') }}
            </p>
        </div>
    @endif
</div>

@if ($searched && ! $sale)
    <div class="rounded-2xl bg-red-50 p-6 text-center text-red-700">{{ __('return.not_found') }}</div>
@endif

@once
    @push('scripts')
    <script>
        onAppReady(function () {
            const $box = $('#invoice-recent');

            if (!$box.length) return;

            const $input = $('#invoice-input');
            const $picks = $box.find('.invoice-pick');

            const open = () => $box.removeClass('hidden');
            const close = () => $box.addClass('hidden');

            $input.on('focus click', open);

            // Typing narrows the same list instead of replacing it, so the
            // employee can key two digits of the number and still recognise
            // the sale by its customer and total.
            $input.on('input', function () {
                const term = $(this).val().trim().toLowerCase();
                let shown = 0;

                $picks.each(function () {
                    const match = !term || $(this).data('search').includes(term);
                    $(this).toggle(match);
                    if (match) shown++;
                });

                $('#invoice-no-match').toggleClass('hidden', shown > 0);
                open();
            });

            $picks.on('click', function () {
                $input.val($(this).data('invoice'));
                close();
                $input.closest('form').trigger('submit');
            });

            $input.on('keydown', function (event) {
                if (event.key === 'Escape') close();
            });

            $(document).on('click', function (event) {
                if (!$(event.target).closest('#invoice-lookup').length) close();
            });
        });
    </script>
    @endpush
@endonce
