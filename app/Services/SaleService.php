<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly PricingEngine $pricing,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Ring up a sale: stock out, customer debt, payment — one transaction.
     *
     * @param  array{customer_id:?int, type:string, discount_amount:int, paid_amount:int, method:string,
     *               note:?string, items:array<array{product_id:int, quantity:float, unit_price:int}>}  $data
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::default();
            $customer = $data['customer_id'] ? Customer::lockForUpdate()->find($data['customer_id']) : null;

            $lines = $this->buildLines($data['items'], $warehouse);

            $subtotal = array_sum(array_column($lines, 'line_total'));
            $discount = min((int) $data['discount_amount'], $subtotal);
            $total = $subtotal - $discount;

            // On an exchange the goods handed back already cover part of the bill.
            $credit = min((int) ($data['credit_amount'] ?? 0), $total);
            $paid = min((int) $data['paid_amount'], $total - $credit) + $credit;
            $due = $total - $paid;

            $this->assertCreditAllowed($customer, $due);

            $sale = Sale::create([
                'invoice_number' => Sequence::next((string) settings('invoice.prefix', 'INV')),
                'customer_id' => $customer?->id,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouse->id,
                'type' => $data['type'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $total,
                'cost_total' => array_sum(array_map(
                    fn ($line) => (int) round($line['quantity'] * $line['unit_cost']),
                    $lines,
                )),
                'paid_amount' => $paid,
                'due_amount' => $due,
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'sold_at' => now(),
            ]);

            foreach ($lines as $line) {
                $sale->items()->create($line);

                $this->stock->move(
                    Product::find($line['product_id']),
                    -$line['quantity'],
                    'sale',
                    $warehouse,
                    reference: $sale,
                    unitCost: $line['unit_cost'],
                );
            }

            if ($customer && $due > 0) {
                $customer->increment('balance', $due);
            }

            // Goods handed back on an exchange pay for part of the new sale.
            if ($credit > 0) {
                $this->payments->against($customer, $credit, 'exchange', $sale);
            }

            if ($paid - $credit > 0) {
                $this->payments->against($customer, $paid - $credit, $data['method'] ?? 'cash', $sale);
            }

            return $sale->load('items', 'customer');
        });
    }

    /** Cancel a sale: stock back, debt back, trail kept. Never deleted. */
    public function void(Sale $sale, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $reason) {
            $sale = Sale::with('items')->lockForUpdate()->find($sale->id);

            if ($sale->isVoided()) {
                throw ValidationException::withMessages(['sale' => __('sale.already_voided')]);
            }

            $warehouse = Warehouse::find($sale->warehouse_id);

            foreach ($sale->items as $item) {
                $this->stock->move(
                    Product::find($item->product_id),
                    (float) $item->quantity,
                    'sale_return',
                    $warehouse,
                    reason: __('sale.voided_movement', ['invoice' => $sale->invoice_number]),
                    reference: $sale,
                );
            }

            if ($sale->customer_id && $sale->due_amount > 0) {
                Customer::whereKey($sale->customer_id)->lockForUpdate()->first()
                    ?->decrement('balance', $sale->due_amount);
            }

            $sale->update([
                'status' => 'voided',
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $sale;
        });
    }

    /** A customer over their limit cannot walk out with more credit. */
    private function assertCreditAllowed(?Customer $customer, int $due): void
    {
        if (! $customer || $due <= 0 || $customer->credit_limit <= 0) {
            return;
        }

        if ($customer->balance + $due > $customer->credit_limit) {
            throw ValidationException::withMessages([
                'paid_amount' => __('sale.credit_limit_reached', [
                    'limit' => money($customer->credit_limit),
                    'balance' => money($customer->balance),
                ]),
            ]);
        }
    }

    /** @return array<array{product_id:int, product_name:string, quantity:float, unit_price:int, unit_cost:int, line_total:int}> */
    private function buildLines(array $items, Warehouse $warehouse): array
    {
        $products = Product::findMany(array_column($items, 'product_id'))->keyBy('id');
        $allowNegative = (bool) settings('allow_negative_stock', false);
        $lines = [];

        foreach ($items as $item) {
            $product = $products[$item['product_id']] ?? null;
            $quantity = (float) $item['quantity'];

            if (! $product || $quantity <= 0) {
                continue;
            }

            $unitPrice = (int) $item['unit_price'];
            $floor = $this->pricing->floorFor($product);

            if ($floor > 0 && $unitPrice < $floor) {
                throw ValidationException::withMessages([
                    'items' => __('sale.below_min_price', ['product' => $product->name, 'min' => money($floor)]),
                ]);
            }

            if (! $allowNegative) {
                $available = (float) $product->inventory()
                    ->where('warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->sum('quantity');

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => __('sale.not_enough_stock', [
                            'product' => $product->name,
                            'available' => (float) $available,
                        ]),
                    ]);
                }
            }

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => (int) $product->cost_price,
                'line_total' => (int) round($quantity * $unitPrice),
            ];
        }

        return $lines;
    }
}
