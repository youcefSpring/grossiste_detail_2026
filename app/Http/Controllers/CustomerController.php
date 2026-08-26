<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request)
    {
        $customers = Customer::query()
            ->search($request->input('q'))
            ->when($request->input('status') === 'debt', fn ($q) => $q->where('balance', '>', 0))
            ->orderBy('name')
            ->paginate(per_page())
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'totalDebt' => (int) Customer::where('balance', '>', 0)->sum('balance'),
        ]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer(['is_active' => true])]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = Customer::create($request->customerData());

        // The POS opens this in a tab-less flow; send them back to selling.
        if ($request->boolean('from_pos')) {
            return redirect()->route('sales.create')->with('status', __('customer.created', ['name' => $customer->name]));
        }

        return $this->done(
            __('customer.created', ['name' => $customer->name]),
            route('customers.show', $customer),
        );
    }

    public function show(Customer $customer)
    {
        return view('customers.show', [
            'customer' => $customer,
            'sales' => $customer->sales()->latest('sold_at')->latest('id')->limit(20)->get(),
            'payments' => $customer->payments()->with('payable')->latest('paid_at')->latest('id')->limit(20)->get(),
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->customerData());

        return $this->done(
            __('customer.updated', ['name' => $customer->name]),
            route('customers.show', $customer),
        );
    }

    /** Customer hands over money against their debt. */
    public function collect(Request $request, Customer $customer)
    {
        abort_unless($request->user()->can('payment.record'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payments->settle(
            $customer,
            centimes($data['amount']),
            $data['method'],
            $data['paid_at'],
            $data['note'] ?? null,
        );

        return $this->done(
            __('customer.collected', ['amount' => $data['amount']]),
            route('customers.show', $customer),
        );
    }
}
