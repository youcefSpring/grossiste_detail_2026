<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'invoice_number', 'customer_id', 'user_id', 'warehouse_id', 'type',
        'subtotal', 'discount_amount', 'total', 'cost_total', 'paid_amount', 'due_amount',
        'status', 'note', 'sold_at', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = ['sold_at' => 'datetime', 'voided_at' => 'datetime'];

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
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function profit(): int
    {
        return $this->total - $this->cost_total;
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }
}
