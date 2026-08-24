<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Customer brings goods back.
     *
     * @param  array<array{sale_item_id:int, quantity:float, condition:string}>  $items
     */
    public function returnSale(Sale $sale, array $items, string $refundMethod, ?string $reason = null): SaleReturn
    {
        return DB::transaction(function () use ($sale, $items, $refundMethod, $reason) {
            $sale = Sale::with('items')->lockForUpdate()->find($sale->id);

            if ($sale->isVoided()) {
                throw ValidationException::withMessages(['sale' => __('return.sale_voided')]);
            }

            $warehouse = Warehouse::find($sale->warehouse_id);
            $lines = $this->buildSaleReturnLines($sale, $items);

            if (! $lines) {
                throw ValidationException::withMessages(['items' => __('return.nothing_selected')]);
            }

            $total = array_sum(array_column($lines, 'line_total'));

            $return = SaleReturn::create([
                'reference' => Sequence::next('RET'),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouse->id,
                'total_amount' => $total,
                'refund_method' => $refundMethod,
                'reason' => $reason,
                'returned_at' => now(),
            ]);

            foreach ($lines as $line) {
                $return->items()->create($line);

                $sale->items()->whereKey($line['sale_item_id'])->increment('returned_quantity', $line['quantity']);

                $product = Product::find($line['product_id']);

                // Everything physically comes back in...
                $this->stock->move(
                    $product,
                    (float) $line['quantity'],
                    'sale_return',
                    $warehouse,
                    reason: __('return.from_invoice', ['invoice' => $sale->invoice_number]),
                    reference: $return,
                );

                // ...but broken goods leave sellable stock again straight away.
                if ($line['condition'] === 'damaged') {
                    $this->stock->move(
                        $product,
                        -(float) $line['quantity'],
                        'damaged',
                        $warehouse,
                        reason: __('return.damaged_on_return', ['invoice' => $sale->invoice_number]),
                        reference: $return,
                    );

                    Inventory::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $product->id)
                        ->increment('damaged_quantity', $line['quantity']);
                }
            }

            $this->settleRefund($sale, $return, $total, $refundMethod);
            $this->refreshSaleStatus($sale);

            return $return->load('items');
        });
    }

    /**
     * Exchange: goods come back, other goods go out, only the difference moves.
     *
     * @param  array<array{sale_item_id:int, quantity:float, condition:string}>  $returnItems
     * @param  array<array{product_id:int, quantity:float, unit_price:int}>  $newItems
     */
    public function exchange(
        Sale $sale,
        array $returnItems,
        array $newItems,
        int $extraPaid,
        string $method,
        ?string $reason = null,
    ): SaleReturn {
        return DB::transaction(function () use ($sale, $returnItems, $newItems, $extraPaid, $method, $reason) {
            $return = $this->returnSale($sale, $returnItems, 'exchange', $reason);

            $newSale = app(SaleService::class)->create([
                'customer_id' => $sale->customer_id,
                'type' => $sale->type,
                'discount_amount' => 0,
                'paid_amount' => $extraPaid,
                // The returned goods pay for part of the new ones.
                'credit_amount' => $return->total_amount,
                'method' => $method,
                'note' => __('return.exchange_of', ['invoice' => $sale->invoice_number]),
                'items' => $newItems,
            ]);

            $return->update(['exchange_sale_id' => $newSale->id]);

            return $return->load('items', 'exchangeSale');
        });
    }

    /**
     * Goods go back to the supplier.
     *
     * @param  array<array{purchase_item_id:int, quantity:float}>  $items
     */
    public function returnPurchase(Purchase $purchase, array $items, ?string $reason = null): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $items, $reason) {
            $purchase = Purchase::with('items')->lockForUpdate()->find($purchase->id);
            $warehouse = Warehouse::find($purchase->warehouse_id);
            $allowNegative = (bool) settings('allow_negative_stock', false);

            $lines = [];

            foreach ($items as $item) {
                $purchaseItem = $purchase->items->firstWhere('id', (int) $item['purchase_item_id']);
                $quantity = (float) $item['quantity'];

                if (! $purchaseItem || $quantity <= 0) {
                    continue;
                }

                $left = (float) $purchaseItem->quantity - (float) $purchaseItem->returned_quantity;

                if ($quantity > $left) {
                    throw ValidationException::withMessages([
                        'items' => __('return.more_than_bought', [
                            'product' => $purchaseItem->product_name,
                            'left' => $left,
                        ]),
                    ]);
                }

                if (! $allowNegative) {
                    $available = (float) Inventory::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $purchaseItem->product_id)
                        ->lockForUpdate()
                        ->sum('quantity');

                    if ($quantity > $available) {
                        throw ValidationException::withMessages([
                            'items' => __('sale.not_enough_stock', [
                                'product' => $purchaseItem->product_name,
                                'available' => $available,
                            ]),
                        ]);
                    }
                }

                $lines[] = [
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'product_name' => $purchaseItem->product_name,
                    'quantity' => $quantity,
                    'unit_cost' => (int) $purchaseItem->unit_cost,
                    'line_total' => (int) round($quantity * $purchaseItem->unit_cost),
                ];
            }

            if (! $lines) {
                throw ValidationException::withMessages(['items' => __('return.nothing_selected')]);
            }

            $total = array_sum(array_column($lines, 'line_total'));

            $return = PurchaseReturn::create([
                'reference' => Sequence::next('RETF'),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouse->id,
                'total_amount' => $total,
                'reason' => $reason,
                'returned_at' => now(),
            ]);

            foreach ($lines as $line) {
                $return->items()->create($line);

                $purchase->items()->whereKey($line['purchase_item_id'])
                    ->increment('returned_quantity', $line['quantity']);

                $this->stock->move(
                    Product::find($line['product_id']),
                    -$line['quantity'],
                    'purchase_return',
                    $warehouse,
                    reason: __('return.to_supplier', ['reference' => $purchase->reference]),
                    reference: $return,
                );
            }

            // We owe the supplier less. If the purchase was already paid, this can
            // go negative, which simply means they owe us a credit.
            Supplier::whereKey($purchase->supplier_id)->lockForUpdate()->first()
                ?->decrement('balance', $total);

            return $return->load('items');
        });
    }

    /** Money (or debt) moves back to the customer. Exchanges settle in the new sale instead. */
    private function settleRefund(Sale $sale, SaleReturn $return, int $total, string $method): void
    {
        if ($method === 'exchange' || $total <= 0) {
            return;
        }

        if ($method === 'credit' && $sale->customer_id) {
            // Knocks the debt down; below zero it becomes shop credit for them.
            Customer::whereKey($sale->customer_id)->lockForUpdate()->first()?->decrement('balance', $total);

            return;
        }

        $this->payments->refund(
            $sale->customer,
            $total,
            $return,
            __('return.refund_for', ['invoice' => $sale->invoice_number]),
        );
    }

    private function refreshSaleStatus(Sale $sale): void
    {
        $sale->load('items');

        $sold = $sale->items->sum(fn ($item) => (float) $item->quantity);
        $returned = $sale->items->sum(fn ($item) => (float) $item->returned_quantity);

        $sale->update([
            'status' => match (true) {
                $returned <= 0 => 'completed',
                $returned >= $sold => 'returned',
                default => 'partially_returned',
            },
        ]);
    }

    /** @return array<array{sale_item_id:int, product_id:int, product_name:string, quantity:float, unit_price:int, line_total:int, condition:string}> */
    private function buildSaleReturnLines(Sale $sale, array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $saleItem = $sale->items->firstWhere('id', (int) $item['sale_item_id']);
            $quantity = (float) $item['quantity'];

            if (! $saleItem || $quantity <= 0) {
                continue;
            }

            $left = (float) $saleItem->quantity - (float) $saleItem->returned_quantity;

            if ($quantity > $left) {
                throw ValidationException::withMessages([
                    'items' => __('return.more_than_sold', [
                        'product' => $saleItem->product_name,
                        'left' => $left,
                    ]),
                ]);
            }

            $condition = in_array($item['condition'] ?? '', SaleReturn::CONDITIONS, true)
                ? $item['condition']
                : 'resellable';

            $lines[] = [
                'sale_item_id' => $saleItem->id,
                'product_id' => $saleItem->product_id,
                'product_name' => $saleItem->product_name,
                'quantity' => $quantity,
                'unit_price' => (int) $saleItem->unit_price,
                'line_total' => (int) round($quantity * $saleItem->unit_price),
                'condition' => $condition,
            ];
        }

        return $lines;
    }
}
