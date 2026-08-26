<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Every movement of money passes through here, so there is one place to read
 * when asking "what happened to the cash, and what does that account owe now?".
 *
 * Money in  = a customer pays us.
 * Money out = we pay a supplier, or refund a customer.
 */
class PaymentService
{
    /**
     * Record a payment against an invoice. The balance is handled by the calling flow.
     * A null party is a walk-in: cash in for a sale, cash out for a purchase,
     * with no account to track it against.
     */
    public function against(?Model $party, int $amount, string $method, ?Model $payable = null, ?string $note = null): Payment
    {
        return $this->write($party, $amount, $method, $payable, today()->toDateString(), $note);
    }

    /**
     * Settle part of a running balance: the money moves and the account drops by the same amount.
     * Used for "customer pays off their debt" and "we pay the supplier".
     */
    public function settle(Model $party, int $amount, string $method, string $paidAt, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($party, $amount, $method, $paidAt, $note) {
            $party->newQuery()->whereKey($party->getKey())->lockForUpdate()->first()
                ?->decrement('balance', $amount);

            return $this->write($party, $amount, $method, null, $paidAt, $note);
        });
    }

    private function write(?Model $party, int $amount, string $method, ?Model $payable, string $paidAt, ?string $note): Payment
    {
        // With no party, the document itself says which way the money went:
        // anything paid against a purchase leaves the till.
        $isSupplier = $party instanceof Supplier
            || $payable instanceof Purchase
            || $payable instanceof PurchaseReturn;

        return Payment::create([
            'direction' => $isSupplier ? 'out' : 'in',
            'party_type' => $isSupplier ? 'supplier' : 'customer',
            'party_id' => $party?->getKey() ?? 0,
            'payable_type' => $payable ? $payable::class : null,
            'payable_id' => $payable?->getKey(),
            'amount' => $amount,
            'method' => $method,
            'user_id' => auth()->id(),
            'paid_at' => $paidAt,
            'note' => $note,
        ]);
    }

    /** Cash handed back to a customer. Leaves the till, so the direction is out. */
    public function refund(?Customer $customer, int $amount, Model $payable, ?string $note = null): Payment
    {
        return tap($this->write($customer, $amount, 'cash', $payable, today()->toDateString(), $note))
            ->update(['direction' => 'out']);
    }
}
