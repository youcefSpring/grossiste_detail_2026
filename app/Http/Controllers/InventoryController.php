<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    /** Product | Stock | Alert level | Status. Nothing else. */
    public function index(Request $request)
    {
        $status = $request->input('status');

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($status === 'out', fn ($q) => $q->outOfStock())
            ->when($status === 'low', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(per_page())
            ->withQueryString();

        return view('inventory.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'counts' => $this->statusCounts(),
        ]);
    }

    /** Full movement ledger, optionally narrowed to one product. */
    public function movements(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['product:id,name', 'user:id,name'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'product' => $request->filled('product_id') ? Product::find($request->input('product_id')) : null,
        ]);
    }

    public function editStock(Product $product)
    {
        return view('inventory.adjust', [
            'product' => $product,
            'current' => (float) $product->inventory()->sum('quantity'),
        ]);
    }

    /** A stock count: the employee types what is really on the shelf. */
    public function updateStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'reason' => ['required', Rule::in(array_keys(StockService::ADJUST_REASONS))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = __('stock.reasons.'.$data['reason']).($data['note'] ? ' — '.$data['note'] : '');

        $movement = $this->stock->setQuantity($product, (float) $data['quantity'], 'adjustment', reason: $reason);

        return redirect()
            ->route('inventory.index')
            ->with('status', $movement
                ? __('stock.adjusted', ['name' => $product->name, 'qty' => (float) $data['quantity']])
                : __('stock.unchanged'));
    }

    /**
     * One aggregate query instead of pulling the whole catalogue into PHP —
     * this runs on every inventory page load.
     */
    /**
     * One grouped pass over the catalogue, counted in SQL.
     * This runs on every inventory page load, so it must not scale with the catalogue in PHP.
     */
    private function statusCounts(): array
    {
        // A full pass over the catalogue; the tab badges do not need to be second-accurate.
        return cache()->remember('inventory.status_counts', now()->addMinute(), fn () => $this->countStatuses());
    }

    private function countStatuses(): array
    {
        $row = Product::query()
            ->where('is_active', true)
            ->selectRaw('count(*) as total,
                         sum(case when stock <= 0 then 1 else 0 end) as out_count,
                         sum(case when stock > 0 and stock <= min_stock then 1 else 0 end) as low_count')
            ->first();

        return [
            'all' => (int) $row->total,
            'low' => (int) $row->low_count,
            'out' => (int) $row->out_count,
        ];
    }
}
