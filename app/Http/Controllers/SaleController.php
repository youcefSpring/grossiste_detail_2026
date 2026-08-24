<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PdfService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request)
    {
        $sales = Sale::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->date('to')))
            ->when($request->input('status') === 'due', fn ($q) => $q->where('due_amount', '>', 0))
            // A cashier sees their own till, a manager sees everything.
            ->when(! $request->user()->can('report.financial') && ! $request->user()->can('sale.void'),
                fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('sold_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    /** The POS screen. */
    public function create()
    {
        return view('sales.pos', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'is_wholesale', 'balance']),
            'quickPicks' => $this->quickPicks(),
        ]);
    }

    /**
     * The handful of products that actually move, so the common sale is a tap
     * rather than a search. Falls back to newest stock in a fresh shop.
     */
    private function quickPicks(int $limit = 12)
    {
        $bestSellers = Product::query()
            ->where('products.is_active', true)
            ->where('products.stock', '>', 0)
            ->join('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', function ($join) {
                $join->on('sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.status', '!=', 'voided')
                    ->where('sales.sold_at', '>=', now()->subDays(30));
            })
            ->groupBy('products.id')
            ->orderByRaw('sum(sale_items.quantity) desc')
            ->select('products.*')
            ->limit($limit)
            ->get();

        if ($bestSellers->count() >= $limit) {
            return $bestSellers;
        }

        // Top up with anything else in stock so the grid is never half empty.
        return $bestSellers->concat(
            Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->whereNotIn('id', $bestSellers->pluck('id'))
                ->orderBy('name')
                ->limit($limit - $bestSellers->count())
                ->get()
        );
    }

    public function store(SaleRequest $request)
    {
        $sale = $this->sales->create($request->saleData());

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', __('sale.created', ['invoice' => $sale->invoice_number]));
    }

    public function show(Sale $sale)
    {
        $sale->load('items', 'customer', 'user', 'payments');

        return view('sales.show', compact('sale'));
    }

    /** Printable invoice — plain page, no app chrome. */
    public function invoice(Request $request, Sale $sale)
    {
        $sale->load('items', 'customer', 'user', 'payments');

        if ($request->input('export') === 'pdf') {
            return app(PdfService::class)->download(
                'sales.invoice-pdf',
                ['sale' => $sale],
                $sale->invoice_number.'.pdf',
            );
        }

        return view('sales.invoice', compact('sale'));
    }

    public function void(Request $request, Sale $sale)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->sales->void($sale, $data['reason']);

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', __('sale.voided', ['invoice' => $sale->invoice_number]));
    }
}
