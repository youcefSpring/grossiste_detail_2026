@extends('pdf.layout')
@section('title', __('report.names.'.$report))
@section('doc-title', __('report.names.'.$report))
@section('doc-meta', $config['dated'] ? $from->format('Y-m-d').' → '.$to->format('Y-m-d') : '')

@section('content')
@php($countColumns = \App\Services\ReportService::COUNT_COLUMNS)

@if ($report === 'financial')
    <table class="totals" style="width: 90mm;">
        @foreach (['revenue', 'cost', 'returns', 'gross_profit', 'expenses', 'net_profit', 'purchases', 'customer_debt', 'supplier_debt'] as $key)
            <tr class="{{ in_array($key, ['gross_profit', 'net_profit']) ? 'grand' : '' }}">
                <td>{{ __('report.columns.financial.'.$key) }}</td>
                <td class="end"><span class="num" dir="ltr">{{ money($rows->$key) }}</span> {{ settings('currency.symbol') }}</td>
            </tr>
        @endforeach
    </table>

@elseif (! count($rows))
    <p class="muted center">{{ __('report.empty') }}</p>

@else
    @php($columns = array_keys((array) $rows->first()))

    <table class="data">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th class="{{ $loop->first ? 'start' : 'end' }}">
                        {{ __("report.columns.{$report}.{$column}") }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $row)
                <tr class="{{ $loop->odd ? '' : 'alt' }}">
                    @foreach ($columns as $column)
                        @php($value = $row->$column)
                        <td class="{{ $loop->first ? 'start' : 'end' }}">
                            @if ($column === 'status')
                                <span class="badge">{{ __('product.stock_status.'.$value) }}</span>
                            @elseif (is_int($value) && ! in_array($column, $countColumns, true))
                                <span class="num" dir="ltr">{{ money($value) }}</span>
                            @elseif (is_numeric($value))
                                <span class="num" dir="ltr">{{ $value }}</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                @foreach ($columns as $column)
                    <td class="{{ $loop->first ? 'start' : 'end' }}">
                        @if ($loop->first)
                            {{ __('report.total') }}
                        @elseif (is_numeric($rows->first()->$column))
                            @php($sum = $totals->$column ?? $rows->sum($column))
                            <span class="num" dir="ltr">{{ is_int($rows->first()->$column) && ! in_array($column, $countColumns, true)
                                ? money($sum) : round($sum, 3) }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        </tfoot>
    </table>
@endif
@endsection
