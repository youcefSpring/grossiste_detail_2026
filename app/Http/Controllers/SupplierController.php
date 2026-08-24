<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->search($request->input('q'))
            ->when($request->input('status') === 'debt', fn ($q) => $q->where('balance', '>', 0))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'totalDebt' => (int) Supplier::where('balance', '>', 0)->sum('balance'),
        ]);
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', __('supplier.created', ['name' => $supplier->name]));
    }

    /** Account statement: what they supplied, what we paid, what is left. */
    public function show(Supplier $supplier)
    {
        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchases' => $supplier->purchases()->latest('purchased_at')->latest('id')->limit(20)->get(),
            'payments' => $supplier->payments()->with('payable')->latest('paid_at')->latest('id')->limit(20)->get(),
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', __('supplier.updated', ['name' => $supplier->name]));
    }

    /** Hand the supplier money against the running balance. */
    public function pay(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->can('payment.record'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payments->settle(
            $supplier,
            centimes($data['amount']),
            $data['method'],
            $data['paid_at'],
            $data['note'] ?? null,
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', __('supplier.paid', ['amount' => $data['amount']]));
    }
}
