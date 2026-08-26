<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category')
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(per_page())
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.form', [
            'product' => new Product(['unit' => 'piece', 'is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
            'stock' => 0,
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->productData();

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $product = Product::create($data);

            if ($request->float('stock') > 0) {
                $this->stock->setQuantity($product, $request->float('stock'), 'opening', reason: __('product.opening_stock'));
            }

            return $product;
        });

        return $this->done(
            __('product.created', ['name' => $product->name]),
            route('products.index'),
        );
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'stock' => (float) $product->stock,
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->productData();

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $product->update($data);

            // Editing the stock field is a stock count, and it leaves an audit trail.
            $this->stock->setQuantity($product, $request->float('stock'), 'adjustment', reason: __('product.stock_edited'));
        });

        return $this->done(
            __('product.updated', ['name' => $product->name]),
            route('products.index'),
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->done(
            __('product.deleted', ['name' => $product->name]),
            route('products.index'),
        );
    }

    /** JSON feed for the jQuery barcode/search box (POS reuses this). */
    public function search(Request $request)
    {
        // Buying prices are not for every role — a cashier must not read them off the POS.
        $showsCost = $request->user()->can('product.cost.view');

        $products = Product::query()
            ->where('is_active', true)
            ->search($request->input('q'))
            ->limit(15)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'barcode' => $p->barcode,
                'sku' => $p->sku,
                'unit' => __('product.units.'.$p->unit),
                'stock' => (float) $p->stock,
                'retail_price' => money($p->retail_price),
                // Raw decimals feed number inputs; the formatted ones are for display.
                'cost_price_raw' => $showsCost ? number_format($p->cost_price / 100, 2, '.', '') : null,
                'retail_price_raw' => number_format($p->retail_price / 100, 2, '.', ''),
                'wholesale_price_raw' => number_format($p->wholesale_price / 100, 2, '.', ''),
                'wholesale_price' => money($p->wholesale_price),
            ]);

        return response()->json($products);
    }
}
