{{-- Rows per page. Lives inside a data-live form, so it applies as it changes. --}}
<label class="flex items-center gap-2 text-sm text-slate-500 whitespace-nowrap">
    <span class="hidden sm:inline">{{ __('app.per_page') }}</span>
    <select name="per_page" class="rounded-lg border-slate-300 py-2.5 tabular-nums">
        @foreach ([25, 50, 100, 200] as $size)
            <option value="{{ $size }}" @selected(per_page() === $size)>{{ $size }}</option>
        @endforeach
    </select>
</label>
