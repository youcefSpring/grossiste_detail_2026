<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returns) {}

    public function index(Request $request)
    {
        return view('returns.index', [
            'saleReturns' => SaleReturn::with('customer:id,name', 'sale:id,invoice_number')
                ->latest('returned_at')->latest('id')->paginate(per_page(), ['*'], 'sales_page'),
            'purchaseReturns' => PurchaseReturn::with('supplier:id,name', 'purchase:id,reference')
                ->latest('returned_at')->latest('id')->paginate(per_page(), ['*'], 'purchases_page'),
        ]);
    }

    /** Look up the invoice, then pick the lines coming back. */
    public function createSaleReturn(Request $request)
    {
        $sale = null;

        if ($invoice = trim((string) $request->input('invoice'))) {
            $sale = Sale::with('items', 'customer')
                ->where('invoice_number', $invoice)
                ->orWhere('id', ctype_digit($invoice) ? (int) $invoice : 0)
                ->first();
        }

        return view('returns.sale', [
            'sale' => $sale,
            'searched' => $request->filled('invoice'),
            'recent' => $this->recentInvoices(),
        ]);
    }

    /**
     * The invoices a customer is most likely to come back with.
     *
     * A return happens minutes or days after the sale, so the last ten
     * invoices cover almost every case — and the employee never has to read a
     * number off a crumpled receipt.
     */
    private function recentInvoices()
    {
        return Sale::with('customer:id,name')
            ->where('status', 'completed')
            ->latest('sold_at')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'invoice_number', 'customer_id', 'total', 'sold_at']);
    }

    public function storeSaleReturn(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.condition' => ['required', Rule::in(SaleReturn::CONDITIONS)],
            'refund_method' => ['required', Rule::in(['cash', 'credit'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Rows left at zero are simply lines the employee did not tick.
        $items = array_values(array_filter($data['items'], fn ($item) => (float) $item['quantity'] > 0));

        $return = $this->returns->returnSale($sale, $items, $data['refund_method'], $data['reason'] ?? null);

        return redirect()
            ->route('returns.show', $return)
            ->with('status', __('return.done', ['amount' => money($return->total_amount)]));
    }

    public function show(SaleReturn $return)
    {
        $return->load('items', 'sale', 'customer', 'user', 'exchangeSale');

        return view('returns.show', compact('return'));
    }

    /** Exchange: same invoice lookup, plus the replacement basket. */
    public function createExchange(Request $request)
    {
        $sale = null;

        if ($invoice = trim((string) $request->input('invoice'))) {
            $sale = Sale::with('items', 'customer')->where('invoice_number', $invoice)->first();
        }

        return view('returns.exchange', [
            'sale' => $sale,
            'searched' => $request->filled('invoice'),
            'recent' => $this->recentInvoices(),
        ]);
    }

    public function storeExchange(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.condition' => ['required', Rule::in(SaleReturn::CONDITIONS)],
            'new_items' => ['required', 'array', 'min:1'],
            'new_items.*.product_id' => ['required', 'exists:products,id'],
            'new_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'new_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'extra_paid' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $return = $this->returns->exchange(
            $sale,
            array_values(array_filter($data['items'], fn ($item) => (float) $item['quantity'] > 0)),
            collect($data['new_items'])->map(fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => centimes($item['unit_price']),
            ])->all(),
            centimes($data['extra_paid'] ?? 0),
            $data['method'],
            $data['reason'] ?? null,
        );

        return redirect()
            ->route('returns.show', $return)
            ->with('status', __('return.exchange_done'));
    }

    /** Supplier return: pick the purchase, pick the lines. */
    public function createPurchaseReturn(Purchase $purchase)
    {
        $purchase->load('items', 'supplier');

        return view('returns.purchase', compact('purchase'));
    }

    public function storePurchaseReturn(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'exists:purchase_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $return = $this->returns->returnPurchase(
            $purchase,
            array_values(array_filter($data['items'], fn ($item) => (float) $item['quantity'] > 0)),
            $data['reason'] ?? null,
        );

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', __('return.supplier_done', ['amount' => money($return->total_amount)]));
    }
}
