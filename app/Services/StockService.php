<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockService
{
    /** Why a shelf count differs from the system. Kept short on purpose. */
    public const ADJUST_REASONS = [
        'count' => 'count',        // physical stock count
        'damaged' => 'damaged',    // broken / expired
        'lost' => 'lost',          // missing or stolen
        'correction' => 'correction', // data entry mistake
    ];

    /**
     * Apply a signed quantity change and record the movement.
     * Always call inside a transaction when other tables change too.
     */
    public function move(
        Product $product,
        float $quantity,
        string $type,
        ?Warehouse $warehouse = null,
        ?string $reason = null,
        ?object $reference = null,
        ?int $unitCost = null,
    ): StockMovement {
        $warehouse ??= Warehouse::default();

        return DB::transaction(function () use ($product, $quantity, $type, $warehouse, $reason, $reference, $unitCost) {
            $row = Inventory::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first()
                ?? Inventory::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                ]);

            $balance = (float) $row->quantity + $quantity;

            $row->update(['quantity' => $balance]);

            // Keep the product's cached total in step, inside this same transaction.
            $product->newQuery()->whereKey($product->id)->update([
                'stock' => Inventory::where('product_id', $product->id)->sum('quantity'),
            ]);

            // The inventory tab badges are counted from these totals.
            cache()->forget('inventory.status_counts');

            return StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balance,
                'unit_cost' => $unitCost,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'user_id' => auth()->id(),
                'reason' => $reason,
            ]);
        });
    }

    /** Set stock to an absolute figure (opening stock, stock count). */
    public function setQuantity(
        Product $product,
        float $newQuantity,
        string $type = 'adjustment',
        ?Warehouse $warehouse = null,
        ?string $reason = null,
    ): ?StockMovement {
        $warehouse ??= Warehouse::default();

        $current = (float) Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $delta = $newQuantity - $current;

        if (abs($delta) < 0.0005) {
            return null;
        }

        return $this->move($product, $delta, $type, $warehouse, $reason);
    }
}
