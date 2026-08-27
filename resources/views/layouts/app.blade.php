@php($rtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('nav.dashboard')) — {{ settings('shop.name') }}</title>

    {{-- Restore the collapsed sidebar before paint, so it never flashes open. --}}
    <script>
        if (localStorage.getItem('sidebar') === 'collapsed') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">

{{-- Thin progress bar while the next page loads --}}
<div id="page-loader" class="fixed inset-x-0 top-0 z-[60] h-0.5 bg-emerald-500 opacity-0 transition-opacity"></div>

<div class="min-h-screen flex">
    @include('partials.sidebar')

    <div id="overlay" class="fixed inset-0 z-30 bg-black/40 lg:hidden hidden"></div>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b flex items-center gap-2 sm:gap-3 px-3 sm:px-4 sticky top-0 z-20">
            {{-- One button, both breakpoints: opens the drawer on mobile, collapses the rail on desktop --}}
            <button id="menu-toggle" type="button"
                    aria-label="{{ __('app.menu') }}"
                    class="p-2 -ms-2 rounded-lg text-xl hover:bg-slate-100">☰</button>

            <h1 class="text-base sm:text-lg font-semibold truncate">@yield('title')</h1>
            <div class="flex-1"></div>

            {{-- Right-hand controls. On a phone they are icons: the label text
                 would eat the whole bar. --}}
            {{-- Language: the flag is the label. Three languages, no text needed
                 until the menu is open. --}}
            @php($flags = ['ar' => ['🇩🇿', 'العربية'], 'fr' => ['🇫🇷', 'Français'], 'en' => ['🇬🇧', 'English']])

            <form id="locale-form" method="POST" action="{{ route('locale') }}" class="shrink-0">
                @csrf
                <details id="locale-menu" class="relative">
                    <summary class="list-none cursor-pointer select-none rounded-lg px-2 py-1.5 text-xl leading-none hover:bg-slate-100"
                             aria-label="{{ __('app.language') }}" title="{{ __('app.language') }}">
                        {{ $flags[app()->getLocale()][0] ?? '🌐' }}
                    </summary>

                    <div class="absolute end-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border bg-white shadow-lg">
                        @foreach ($flags as $code => [$flag, $label])
                            <button type="submit" name="locale" value="{{ $code }}"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-start text-sm hover:bg-slate-50
                                           {{ app()->getLocale() === $code ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-700' }}">
                                <span class="text-lg leading-none">{{ $flag }}</span>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </details>
            </form>

            @include('partials.alerts')

            <span class="text-sm text-slate-600 hidden md:inline truncate max-w-40">{{ auth()->user()->name }}</span>

            {{-- One control at every width: the power mark ends the session. --}}
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <x-action icon="logout" :label="__('app.logout')" tone="danger" form="logout-form" />
            </form>
        </header>

        <main class="flex-1 p-4 sm:p-6 overflow-x-hidden">
            @yield('content')
        </main>
    </div>
</div>

<div id="toasts" class="fixed bottom-4 end-4 z-50"></div>

<x-modal />

<script>
    // Messages the scripts need but cannot translate themselves.
    window.__messages = @json(['error' => __('app.error'), 'loading' => __('app.loading')]);
</script>

@if (session('status'))
    <div data-flash="success" data-message="{{ session('status') }}" class="hidden"></div>
@endif
@if (session('error'))
    <div data-flash="error" data-message="{{ session('error') }}" class="hidden"></div>
@endif

{{-- @vite emits a deferred module, so inline page scripts must wait for it. --}}
<script>
    window.onAppReady = function (callback) {
        window.__appReady ? callback() : document.addEventListener('app:ready', callback, { once: true });
    };
</script>

@stack('scripts')
</body>
</html>
