@php($rtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>

    {{-- Plain CSS only: mPDF understands none of Tailwind. --}}
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #0f172a; }
        h1 { font-size: 15pt; margin: 0 0 2mm; }
        .muted { color: #64748b; }
        .small { font-size: 8pt; }
        .end { text-align: {{ $rtl ? 'left' : 'right' }}; }
        .start { text-align: {{ $rtl ? 'right' : 'left' }}; }
        .center { text-align: center; }
        /* money() uses a space for thousands; without isolation the bidi
           algorithm turns "2 350,00" into "350,00 2" in an Arabic page. */
        .num { unicode-bidi: isolate; direction: ltr; }
        .bold { font-weight: bold; }

        .head { border-bottom: 0.6pt solid #cbd5e1; padding-bottom: 3mm; margin-bottom: 4mm; }
        .head td { vertical-align: top; border: none; padding: 0; }

        /* Fully ruled grid: a printed document is read across, and the
           borders keep the eye on the right row. */
        table.data { width: 100%; border-collapse: collapse; border: 0.5pt solid #94a3b8; }
        table.data th {
            background: #e2e8f0; font-weight: bold; padding: 2mm 2.2mm;
            border: 0.5pt solid #94a3b8; text-align: {{ $rtl ? 'right' : 'left' }};
        }
        table.data td { padding: 1.8mm 2.2mm; border: 0.4pt solid #cbd5e1; }
        table.data tbody tr.alt td { background: #f8fafc; }
        table.data tfoot td { border: 0.5pt solid #94a3b8; font-weight: bold; background: #e2e8f0; }

        .totals { width: 68mm; border-collapse: collapse; border: 0.5pt solid #94a3b8; }
        .totals td { padding: 1.6mm 2.2mm; border: 0.4pt solid #cbd5e1; }
        .totals tr.grand td { font-size: 12pt; font-weight: bold; background: #e2e8f0; border: 0.5pt solid #94a3b8; }

        .badge { background: #f1f5f9; padding: 0.6mm 1.6mm; border-radius: 2mm; font-size: 8pt; }
        .foot { margin-top: 8mm; border-top: 0.3pt solid #e2e8f0; padding-top: 2mm; }
    </style>
</head>
<body>

<table class="head" width="100%">
    <tr>
        <td width="60%">
            <h1>{{ settings('shop.name') }}</h1>
            @if (settings('shop.address'))
                <div class="muted small">{{ settings('shop.address') }}</div>
            @endif
            @if (settings('shop.phone'))
                <div class="muted small">{{ settings('shop.phone') }}</div>
            @endif
        </td>
        <td width="40%" class="end">
            <div class="bold">@yield('doc-title')</div>
            @hasSection('doc-meta')
                <div class="muted small" dir="ltr">&#8206;@yield('doc-meta')&#8206;</div>
            @endif
        </td>
    </tr>
</table>

@yield('content')

</body>
</html>
