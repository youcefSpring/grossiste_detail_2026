@extends('layouts.app')
@section('title', __('nav.users'))

@section('content')
<div class="space-y-4" data-live-root>
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 text-sm p-4">{{ $errors->first() }}</div>
    @endif

    <form method="GET" data-live class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2 items-center">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('user.search_hint') }}"
               class="flex-1 min-w-48 rounded-lg border-slate-300 px-3 py-2.5">

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>

        @include('partials.per-page')

        <a href="{{ route('users.create') }}" data-modal data-modal-title="{{ __('user.add') }}"
           class="rounded-lg bg-emerald-600 text-white px-5 py-2.5 ms-auto">+ {{ __('user.add') }}</a>
    </form>

    <div class="space-y-2">
        @forelse ($users as $user)
            <div class="bg-white rounded-2xl shadow-sm p-4 flex flex-wrap items-center justify-between gap-3
                        {{ $user->is_active ? '' : 'opacity-50' }}">
                <div class="min-w-0">
                    <div class="font-medium">
                        {{ $user->name }}
                        @foreach ($user->roles as $role)
                            <span class="ms-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-600">
                                {{ __('user.roles.'.$role->name) }}
                            </span>
                        @endforeach
                    </div>
                    <div class="text-sm text-slate-500">{{ $user->email }}</div>
                    @if ($user->last_login_at)
                        <div class="text-xs text-slate-400 tabular-nums">
                            {{ __('user.last_login') }} {{ $user->last_login_at->format('Y-m-d H:i') }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <x-action icon="edit" :label="__('common.edit')" :href="route('users.edit', $user)"
                              data-modal data-modal-title="{{ __('user.edit') }}" />

                    @unless ($user->is(auth()->user()))
                        <form method="POST" action="{{ route('users.toggle', $user) }}">
                            @csrf
                            <x-action :icon="$user->is_active ? 'disable' : 'enable'"
                                      :label="$user->is_active ? __('user.disable') : __('user.enable')"
                                      :tone="$user->is_active ? 'danger' : 'primary'"
                                      :confirm="$user->is_active ? __('user.disable_confirm') : __('user.enable_confirm')"
                                      type="submit" />
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('user.none') }}</div>
        @endforelse
    </div>

    {{ $users->links() }}
</div>
@endsection
