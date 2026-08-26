<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Record a purchase: stock in, supplier balance, payment — all or nothing.
     *
     * The supplier is optional: a cash purchase from someone with no account
     * carries no balance, the same way a walk-in sale carries no customer.
     *
     * @param  array{supplier_id:?int, purchased_at:string, discount_amount:int, paid_amount:int,
     *               method:string, note:?string, items:array<array{product_id:int, quantity:float, unit_cost:int}>}  $data
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $supplier = $data['supplier_id']
                ? Supplier::lockForUpdate()->findOrFail($data['supplier_id'])
                : null;
            $warehouse = Warehouse::default();

            $lines = $this->buildLines($data['items']);
            $subtotal = array_sum(array_column($lines, 'line_total'));
            $discount = min((int) $data['discount_amount'], $subtotal);
            $total = $subtotal - $discount;
            $paid = min((int) $data['paid_amount'], $total);

            $purchase = Purchase::create([
                'reference' => Sequence::next('ACH'),
                'supplier_id' => $supplier?->id,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouse->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'due_amount' => $total - $paid,
                'status' => 'received',
                'note' => $data['note'] ?? null,
                'purchased_at' => $data['purchased_at'],
            ]);

            foreach ($lines as $line) {
                $purchase->items()->create($line);

                $product = Product::lockForUpdate()->find($line['product_id']);

                $this->stock->move(
                    $product,
                    (float) $line['quantity'],
                    'purchase',
                    $warehouse,
                    reference: $purchase,
                    unitCost: $line['unit_cost'],
                );

                // The shop wants the latest buying price to drive the margin they see.
                $product->update(['cost_price' => $line['unit_cost']]);
            }

            // What we still owe them rides on the supplier balance. With no
            // supplier the purchase was paid in full, so there is nothing to carry.
            $supplier?->increment('balance', $total - $paid);

            if ($paid > 0) {
                $this->payments->against($supplier, $paid, $data['method'] ?? 'cash', $purchase);
            }

            return $purchase->load('items', 'supplier');
        });
    }

    /** @return array<array{product_id:int, product_name:string, quantity:float, unit_cost:int, line_total:int}> */
    private function buildLines(array $items): array
    {
        $products = Product::findMany(array_column($items, 'product_id'))->keyBy('id');
        $lines = [];

        foreach ($items as $item) {
            $product = $products[$item['product_id']] ?? null;

            if (! $product || (float) $item['quantity'] <= 0) {
                continue;
            }

            $quantity = (float) $item['quantity'];
            $unitCost = (int) $item['unit_cost'];

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => (int) round($quantity * $unitCost),
            ];
        }

        return $lines;
    }
}
