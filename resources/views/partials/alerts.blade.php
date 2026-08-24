@php($alerts = \App\Support\Alerts::for(auth()->user()))

<details class="relative">
    <summary class="cursor-pointer select-none list-none p-2 text-xl">
        🔔
        @if ($alerts)
            <span class="absolute top-0 end-0 rounded-full bg-red-600 text-white text-[10px] leading-none px-1.5 py-1 tabular-nums">
                {{ count($alerts) }}
            </span>
        @endif
    </summary>

    <div class="absolute end-0 z-30 mt-2 w-72 rounded-xl bg-white shadow-lg border overflow-hidden">
        @forelse ($alerts as $alert)
            @php($tones = ['red' => 'text-red-700', 'amber' => 'text-amber-700', 'slate' => 'text-slate-600'])
            <a href="{{ $alert['url'] }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50 border-b last:border-0">
                <span class="text-sm">{{ __('alert.'.$alert['key']) }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums {{ $tones[$alert['tone']] }}">
                    {{ $alert['count'] }}
                </span>
            </a>
        @empty
            <p class="px-4 py-6 text-center text-sm text-slate-500">{{ __('alert.none') }}</p>
        @endforelse
    </div>
</details>
