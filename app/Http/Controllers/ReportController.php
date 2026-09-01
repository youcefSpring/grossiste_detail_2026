<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Each report: the query, its columns, and how a row becomes a CSV line. */
    private const REPORTS = [
        'sales_day' => ['method' => 'salesByDay', 'permission' => 'report.sales', 'dated' => true],
        'sales_product' => ['method' => 'salesByProduct', 'permission' => 'report.sales', 'dated' => true],
        'sales_employee' => ['method' => 'salesByEmployee', 'permission' => 'report.sales', 'dated' => true],
        'purchases_supplier' => ['method' => 'purchasesBySupplier', 'permission' => 'report.inventory', 'dated' => true],
        'inventory' => ['method' => 'inventoryValuation', 'permission' => 'report.inventory', 'dated' => false],
        'expenses' => ['method' => 'expensesByCategory', 'permission' => 'report.financial', 'dated' => true],
        'financial' => ['method' => 'financialSummary', 'permission' => 'report.financial', 'dated' => true],
    ];

    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request)
    {
        return view('reports.index', [
            'available' => collect(self::REPORTS)
                ->filter(fn ($config) => $request->user()->can($config['permission']))
                ->keys(),
        ]);
    }

    public function show(Request $request, string $report)
    {
        $config = $this->config($request, $report);
        [$from, $to] = $this->range($request);

        $result = $config['dated']
            ? $this->reports->{$config['method']}($from, $to)
            : $this->reports->{$config['method']}();

        // A report may hand back a query so a big catalogue can be streamed instead of loaded.
        $isQuery = $result instanceof Builder;

        if ($request->input('export') === 'csv') {
            return $this->csv($report, $isQuery ? $result->lazy() : $result, $from, $to);
        }

        if ($request->input('export') === 'pdf') {
            return app(PdfService::class)->download('reports.pdf', [
                'report' => $report,
                'rows' => $isQuery ? $result->get() : $result,
                'totals' => $report === 'inventory' ? $this->reports->inventoryTotals() : null,
                'from' => $from,
                'to' => $to,
                'config' => $config,
            ], sprintf('%s_%s_%s.pdf', $report, $from->format('Ymd'), $to->format('Ymd')), 'L');
        }

        return view('reports.show', [
            'report' => $report,
            'rows' => $isQuery ? $result->paginate(per_page(100))->withQueryString() : $result,
            'totals' => $report === 'inventory' ? $this->reports->inventoryTotals() : null,
            'from' => $from,
            'to' => $to,
            'config' => $config,
        ]);
    }

    private function config(Request $request, string $report): array
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        abort_unless($request->user()->can(self::REPORTS[$report]['permission']), 403);

        return self::REPORTS[$report];
    }

    private function range(Request $request): array
    {
        return [
            $request->date('from') ?? now()->startOfMonth(),
            $request->date('to') ?? now(),
        ];
    }

    /** CSV with a UTF-8 BOM so Excel opens Arabic correctly. Streams row by row. */
    private function csv(string $report, mixed $rows, $from, $to): StreamedResponse
    {
        // The financial summary is a single object; everything else is iterable.
        if (is_object($rows) && ! $rows instanceof \Traversable) {
            $rows = [$rows];
        }

        $filename = sprintf('%s_%s_%s.csv', $report, $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($rows, $report) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $wroteHeader = false;

            foreach ($rows as $row) {
                $fields = (array) $row;

                if (! $wroteHeader) {
                    fputcsv($handle, array_map(
                        fn ($column) => self::csvSafe(__("report.columns.{$report}.{$column}")),
                        array_keys($fields),
                    ), ';');

                    $wroteHeader = true;
                }

                $line = [];

                foreach ($fields as $column => $value) {
                    // Counts are counts; only money columns get the centimes treatment.
                    $line[] = self::csvSafe(
                        is_int($value) && ! in_array($column, ReportService::COUNT_COLUMNS, true)
                            ? money($value)
                            : $value,
                    );
                }

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Defuse a spreadsheet formula.
     *
     * A product named "=cmd|'/c calc'!A1" is a formula to Excel, not a label:
     * opening the export would run it. Prefixing with an apostrophe makes the
     * cell text, which is what a report row always is.
     */
    private static function csvSafe(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }
}
