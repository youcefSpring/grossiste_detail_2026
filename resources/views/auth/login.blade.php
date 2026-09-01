@php($rtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.login') }} — {{ settings('shop.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
<div class="min-h-screen flex items-center justify-center p-4">
    <form method="POST" action="{{ route('login') }}"
          class="w-full max-w-sm bg-white rounded-2xl shadow-sm p-6 space-y-4">
        @csrf
        <h1 class="text-xl font-semibold text-center">{{ settings('shop.name') }}</h1>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 text-red-700 text-sm p-3">{{ $errors->first() }}</div>
        @endif

        <label class="block space-y-1">
            <span class="text-sm text-slate-600">{{ __('auth.email') }}</span>
            <input type="email" name="email" value="admin@grossiste.dz" required autofocus
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>

        <label class="block space-y-1">
            <span class="text-sm text-slate-600">{{ __('auth.password') }}</span>
            <input type="password" name="password" required value="password"
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" checked class="rounded border-slate-300">
            {{ __('auth.remember') }}
        </label>

        <button class="w-full rounded-lg bg-slate-900 text-white py-3 font-medium">
            {{ __('auth.login') }}
        </button>
    </form>
</div>
</body>
</html>
