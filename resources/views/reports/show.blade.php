@extends('layouts.app')
@section('title', __('report.names.'.$report))

@section('content')
@php($isSummary = $report === 'financial')

<div class="space-y-4">

    {{-- Filters, hidden when printing --}}
    <form method="GET" class="no-print bg-white rounded-2xl shadow-sm p-3 flex flex-wrap gap-2">
        @if ($config['dated'])
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                   class="rounded-lg border-slate-300 px-3 py-2.5">
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                   class="rounded-lg border-slate-300 px-3 py-2.5">
            <button class="rounded-lg bg-slate-900 text-white px-5 py-2.5">{{ __('app.search') }}</button>
        @endif

        <div class="flex items-center gap-1 ms-auto">
            <x-action icon="print" :label="__('report.print')" onclick="window.print()" />

            <x-action icon="pdf" :label="__('report.export_pdf')" tone="danger"
                      :href="route('reports.show', array_merge([$report], request()->query(), ['export' => 'pdf']))" />

            <x-action icon="excel" :label="__('report.export')" tone="primary"
                      :href="route('reports.show', array_merge([$report], request()->query(), ['export' => 'csv']))" />
        </div>
    </form>

    {{-- Printed header --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 print:shadow-none print:rounded-none">
        <div class="flex flex-wrap items-baseline justify-between gap-2 border-b pb-3 mb-4">
            <div>
                <div class="text-lg font-semibold">{{ __('report.names.'.$report) }}</div>
                <div class="text-sm text-slate-500">{{ settings('shop.name') }}</div>
            </div>
            @if ($config['dated'])
                <div class="text-sm text-slate-500 tabular-nums">
                    {{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}
                </div>
            @endif
        </div>

        @if ($isSummary)
            {{-- One figure per line, biggest at the bottom --}}
            <div class="max-w-md space-y-2">
                @foreach (['revenue', 'cost', 'returns', 'gross_profit', 'expenses', 'net_profit', 'purchases', 'customer_debt', 'supplier_debt'] as $key)
                    <div class="flex justify-between py-2 border-b last:border-0
                                {{ in_array($key, ['gross_profit', 'net_profit']) ? 'font-semibold text-lg' : '' }}
                                {{ $key === 'net_profit' ? ($rows->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700') : '' }}">
                        <span class="{{ in_array($key, ['gross_profit', 'net_profit']) ? '' : 'text-slate-500' }}">
                            {{ __('report.columns.financial.'.$key) }}
                        </span>
                        <span class="tabular-nums">{{ money($rows->$key) }}</span>
                    </div>
                @endforeach
            </div>
        @elseif (! count($rows))
            <p class="py-10 text-center text-slate-500">{{ __('report.empty') }}</p>
        @else
            @php($columns = array_keys((array) $rows->first()))
            <div class="table-card table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th class="{{ $loop->first ? '' : 'num' }}">
                                    {{ __("report.columns.{$report}.{$column}") }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($columns as $column)
                                    @php($value = $row->$column)
                                    <td class="{{ $loop->first ? '' : 'num' }}">
                                        @if ($column === 'status')
                                            <x-stock-badge :status="$value" />
                                        @elseif (is_int($value) && ! in_array($column, \App\Services\ReportService::COUNT_COLUMNS, true))
                                            <bdi>{{ money($value) }}</bdi>
                                        @elseif (is_numeric($value))
                                            <bdi>{{ $value }}</bdi>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Totals for every money column --}}
                    <tfoot>
                        <tr>
                            @foreach ($columns as $column)
                                <td class="{{ $loop->first ? '' : 'num' }}">
                                    @if ($loop->first)
                                        {{ __('report.total') }}
                                    @elseif (is_numeric($rows->first()->$column))
                                        @php($sum = $totals->$column ?? $rows->sum($column))
                                        <bdi>{{ is_int($rows->first()->$column) && ! in_array($column, \App\Services\ReportService::COUNT_COLUMNS, true)
                                            ? money($sum) : round($sum, 3) }}</bdi>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    @if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="no-print">{{ $rows->links() }}</div>
    @endif

    <a href="{{ route('reports.index') }}" class="no-print inline-block rounded-lg border px-6 py-3">
        {{ __('nav.reports') }}
    </a>
</div>
@endsection
