<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'product_name', 'quantity', 'returned_quantity', 'unit_cost', 'line_total'];

    protected $casts = ['quantity' => 'decimal:3', 'returned_quantity' => 'decimal:3'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
