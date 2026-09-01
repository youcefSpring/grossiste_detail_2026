@extends('layouts.app')
@section('title', __('profile.title'))

@section('content')
<div class="grid gap-4 items-start xl:grid-cols-2">

    {{-- Who you are --}}
    <form method="POST" action="{{ route('profile.update') }}" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        @csrf
        @method('PUT')

        <h2 class="font-medium">{{ __('profile.details') }}</h2>

        @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('user.fields.name') }} <span class="text-red-500">*</span></span>
            <input name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('user.fields.email') }} <span class="text-red-500">*</span></span>
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5">
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">{{ __('user.fields.phone') }}</span>
                <input name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel"
                       placeholder="0X XX XX XX XX"
                       class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
            </label>
        </div>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('user.fields.locale') }}</span>
            <select name="locale" class="w-full rounded-lg border-slate-300 py-2.5">
                @foreach (['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        {{-- The role is read-only here: only a user manager changes it. --}}
        <div class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-600">
            {{ __('user.fields.role') }}:
            <span class="font-medium">{{ __('user.roles.'.($user->roles->first()?->name ?? 'sales')) }}</span>
        </div>

        <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
    </form>

    {{-- Password: its own form, so a failed password never loses the details above. --}}
    <form method="POST" action="{{ route('profile.password') }}" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        @csrf
        @method('PUT')

        <h2 class="font-medium">{{ __('profile.password') }}</h2>

        @error('current_password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('profile.current_password') }} <span class="text-red-500">*</span></span>
            <input name="current_password" type="password" autocomplete="current-password" required
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('profile.new_password') }} <span class="text-red-500">*</span></span>
            <input name="password" type="password" autocomplete="new-password" required
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>

        <label class="block space-y-1">
            <span class="text-sm font-medium">{{ __('user.fields.password_confirmation') }} <span class="text-red-500">*</span></span>
            <input name="password_confirmation" type="password" autocomplete="new-password" required
                   class="w-full rounded-lg border-slate-300 px-3 py-2.5">
        </label>

        <button class="w-full rounded-lg bg-slate-900 text-white py-3 font-medium">{{ __('profile.change_password') }}</button>
    </form>
</div>
@endsection
