<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use RecordsActivity;

    public const CONDITIONS = ['resellable', 'damaged'];

    protected $fillable = [
        'reference', 'sale_id', 'customer_id', 'user_id', 'warehouse_id',
        'total_amount', 'refund_method', 'exchange_sale_id', 'reason', 'returned_at',
    ];

    protected $casts = ['returned_at' => 'datetime'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function exchangeSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'exchange_sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function isExchange(): bool
    {
        return $this->refund_method === 'exchange';
    }
}
