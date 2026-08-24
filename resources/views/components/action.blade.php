@props([
    'icon',
    'label',            // shown as the tooltip and read by screen readers
    'href' => null,
    'tone' => 'default', // default | primary | danger
    'form' => null,      // submit a form elsewhere on the page
    'confirm' => null,
])

@php
    $tones = [
        'default' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        'primary' => 'text-emerald-700 hover:bg-emerald-50',
        'danger' => 'text-red-600 hover:bg-red-50',
    ];

    $classes = 'tip inline-flex items-center justify-center w-9 h-9 rounded-lg transition '.$tones[$tone];

    // The label is the tooltip; it is never rendered as visible text.
    $shared = [
        'class' => $classes,
        'aria-label' => $label,
        'data-tip' => $label,
    ];

    if ($confirm) {
        $shared['data-confirm'] = $confirm;
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge($shared) }}>
        <x-icon :name="$icon" />
        <span class="sr-only">{{ $label }}</span>
    </a>
@else
    <button type="{{ $form ? 'submit' : 'button' }}" @if ($form) form="{{ $form }}" @endif
            {{ $attributes->merge($shared) }}>
        <x-icon :name="$icon" />
        <span class="sr-only">{{ $label }}</span>
    </button>
@endif
