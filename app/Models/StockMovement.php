<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = [
        'opening', 'purchase', 'sale', 'sale_return',
        'purchase_return', 'adjustment', 'transfer_in', 'transfer_out', 'damaged',
    ];

    protected $fillable = [
        'warehouse_id', 'product_id', 'type', 'quantity', 'balance_after',
        'unit_cost', 'reference_type', 'reference_id', 'user_id', 'reason',
    ];

    protected $casts = ['quantity' => 'decimal:3', 'balance_after' => 'decimal:3'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
