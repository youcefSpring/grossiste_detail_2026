@extends('layouts.app')
@section('title', __('nav.reports'))

@section('content')
@php
    $icons = [
        'sales_day' => '📅', 'sales_product' => '🏆', 'sales_employee' => '👤',
        'purchases_supplier' => '🚚', 'inventory' => '📦', 'expenses' => '💸', 'financial' => '💰',
    ];
@endphp

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($available as $report)
        <a href="{{ route('reports.show', $report) }}"
           class="bg-white rounded-2xl shadow-sm p-5 hover:bg-slate-50 flex items-start gap-3">
            <span class="text-2xl">{{ $icons[$report] ?? '📊' }}</span>
            <span>
                <span class="block font-medium">{{ __('report.names.'.$report) }}</span>
                <span class="block text-sm text-slate-500">{{ __('report.hints.'.$report) }}</span>
            </span>
        </a>
    @empty
        <div class="bg-white rounded-2xl p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">
            {{ __('report.none_allowed') }}
        </div>
    @endforelse
</div>
@endsection
