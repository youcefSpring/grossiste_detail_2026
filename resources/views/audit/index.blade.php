@extends('layouts.app')
@section('title', __('nav.audit'))

@section('content')
<div class="space-y-4">
    <form method="GET" class="bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        <select name="user_id" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('audit.all_users') }}</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>

        <select name="action" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('audit.all_actions') }}</option>
            @foreach (['created', 'updated', 'deleted'] as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ __('audit.actions.'.$action) }}</option>
            @endforeach
        </select>

        <select name="type" class="rounded-lg border-slate-300 py-2.5">
            <option value="">{{ __('audit.all_types') }}</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>
                    {{ __('audit.types.'.strtolower(class_basename($type))) }}
                </option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 px-3 py-2.5">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 px-3 py-2.5">

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>
    </form>

    <div class="space-y-2">
        @forelse ($logs as $log)
            @php($colours = ['created' => 'bg-emerald-50 text-emerald-700', 'updated' => 'bg-sky-50 text-sky-700', 'deleted' => 'bg-red-50 text-red-700'])
            <details class="bg-white rounded-2xl shadow-sm">
                <summary class="cursor-pointer select-none p-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $colours[$log->action] }}">
                        {{ __('audit.actions.'.$log->action) }}
                    </span>
                    <span class="font-medium">{{ __('audit.types.'.$log->typeKey()) }}</span>
                    @if ($log->label)
                        <span class="text-slate-500 tabular-nums">{{ $log->label }}</span>
                    @endif
                    <span class="ms-auto text-xs text-slate-400 tabular-nums whitespace-nowrap">
                        {{ $log->user?->name ?? '—' }} · {{ $log->created_at->format('Y-m-d H:i') }}
                    </span>
                </summary>

                <div class="border-t p-4">
                    @if ($log->action === 'updated' && $log->new_values)
                        <div class="table-card table-scroll">
                            <table class="table min-w-[380px]">
                                <thead>
                                    <tr>
                                        <th>{{ __('audit.field') }}</th>
                                        <th>{{ __('audit.before') }}</th>
                                        <th>{{ __('audit.after') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($log->new_values as $field => $after)
                                        <tr>
                                            <td class="font-medium">{{ $field }}</td>
                                            <td class="text-red-700">{{ $log->old_values[$field] ?? '—' }}</td>
                                            <td class="text-emerald-700">{{ $after ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <pre class="text-xs text-slate-600 whitespace-pre-wrap">{{ json_encode($log->new_values ?: $log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif

                    @if ($log->ip)
                        <div class="mt-3 text-xs text-slate-400 tabular-nums">IP {{ $log->ip }}</div>
                    @endif
                </div>
            </details>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-slate-500">{{ __('audit.none') }}</div>
        @endforelse
    </div>

    {{ $logs->links() }}
</div>
@endsection
