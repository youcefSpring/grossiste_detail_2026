<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function index(Request $request)
    {
        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->input('supplier_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('purchased_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('purchased_at', '<=', $request->date('to')))
            ->when($request->input('status') === 'due', fn ($q) => $q->where('due_amount', '>', 0))
            ->latest('purchased_at')
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('purchases.form', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PurchaseRequest $request)
    {
        $purchase = $this->purchases->create($request->purchaseData());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', __('purchase.created', ['reference' => $purchase->reference]));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items', 'supplier', 'user', 'payments');

        return view('purchases.show', compact('purchase'));
    }
}
