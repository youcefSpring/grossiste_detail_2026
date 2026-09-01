{{--
    One dialog for the whole app. modal.js fetches a form into #modal-body and
    submits it over AJAX; nothing here is page-specific.
--}}
<div id="modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>

    {{-- The scroller covers the backdrop, so it carries the outside-click itself. --}}
    <div id="modal-scroll" class="absolute inset-0 flex items-start justify-center p-2 sm:p-6 overflow-y-auto">
        <div id="modal-panel"
             class="relative w-full max-w-4xl bg-slate-50 rounded-2xl shadow-xl my-auto
                    transition duration-150 opacity-0 translate-y-2">

            <div class="flex items-center gap-3 px-5 py-4 border-b bg-white rounded-t-2xl sticky top-0 z-10">
                <h2 id="modal-title" class="font-semibold truncate flex-1"></h2>
                <button type="button" data-modal-dismiss aria-label="{{ __('app.close') }}"
                        class="p-2 -me-2 rounded-lg text-slate-500 hover:bg-slate-100 text-xl leading-none">&times;</button>
            </div>

            {{-- Skeleton while the form is on its way; replaced by the response. --}}
            <div id="modal-body" class="p-4 sm:p-5">
                <div id="modal-skeleton" class="space-y-3 animate-pulse">
                    <div class="h-4 w-1/3 rounded bg-slate-200"></div>
                    <div class="h-11 rounded-lg bg-slate-200"></div>
                    <div class="h-11 rounded-lg bg-slate-200"></div>
                    <div class="h-11 w-2/3 rounded-lg bg-slate-200"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Destructive actions get their own small dialog rather than window.confirm. --}}
<div id="confirm-modal" class="fixed inset-0 z-[55] hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/50" data-confirm-dismiss></div>

    <div id="confirm-scroll" class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl p-5 space-y-4">
            <p id="confirm-message" class="text-slate-700"></p>

            <div class="flex gap-2">
                <button type="button" data-confirm-dismiss
                        class="flex-1 rounded-lg border py-2.5">{{ __('app.cancel') }}</button>
                <button type="button" id="confirm-accept"
                        class="flex-1 rounded-lg bg-red-600 text-white py-2.5">{{ __('app.confirm') }}</button>
            </div>
        </div>
    </div>
</div>
