@extends(modal_layout())
@section('title', $user->exists ? __('user.edit') : __('user.add'))

@section('content')
<form method="POST" class="space-y-4"
      action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4 space-y-1">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid gap-4 items-start xl:grid-cols-[minmax(0,1fr)_380px]">

        {{-- The role is the whole permission story, so it gets the room --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('user.fields.name') }} <span class="text-red-500">*</span></span>
                    <input name="name" value="{{ old('name', $user->name) }}" required autofocus
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-lg">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('user.fields.phone') }}</span>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel"
                           placeholder="0X XX XX XX XX"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5 tabular-nums">
                </label>
            </div>

            <div class="space-y-2">
                <span class="text-sm font-medium">{{ __('user.fields.role') }} <span class="text-red-500">*</span></span>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer
                                      has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                            <input type="radio" name="role" value="{{ $role }}" required class="mt-1 text-slate-900"
                                   @checked(old('role', $user->roles->first()?->name ?? 'sales') === $role)>
                            <span>
                                <span class="block font-medium text-sm">{{ __('user.roles.'.$role) }}</span>
                                <span class="block text-xs text-slate-500">{{ __('user.role_hints.'.$role) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sign-in details --}}
        <div class="space-y-4 xl:sticky xl:top-4">
            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
                <h2 class="font-medium">{{ __('auth.login') }}</h2>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('user.fields.email') }} <span class="text-red-500">*</span></span>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">
                        {{ __('user.fields.password') }}
                        @unless ($user->exists) <span class="text-red-500">*</span> @endunless
                    </span>
                    <input name="password" type="password" autocomplete="new-password"
                           {{ $user->exists ? '' : 'required' }}
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                    @if ($user->exists)
                        <span class="text-xs text-slate-400">{{ __('user.password_hint') }}</span>
                    @endif
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('user.fields.password_confirmation') }}</span>
                    <input name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full rounded-lg border-slate-300 px-3 py-2.5">
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium">{{ __('user.fields.locale') }}</span>
                    <select name="locale" class="w-full rounded-lg border-slate-300 py-2.5">
                        @foreach (['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                           @checked(old('is_active', $user->is_active ?? true))>
                    <span class="text-sm">{{ __('user.fields.is_active') }}</span>
                </label>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                <button class="w-full rounded-lg bg-emerald-600 text-white py-3 font-medium">{{ __('app.save') }}</button>
                <a href="{{ route('users.index') }}" data-modal-close
                   class="block text-center rounded-lg border py-3">{{ __('app.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection
