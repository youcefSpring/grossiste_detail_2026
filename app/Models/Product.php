<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use RecordsActivity, SoftDeletes;

    /** Units a shop actually uses. Kept as a plain list — no extra table. */
    public const UNITS = ['piece', 'kg', 'litre', 'metre', 'box', 'carton', 'pack'];

    protected $fillable = [
        'name', 'category_id', 'barcode', 'sku', 'unit',
        'cost_price', 'retail_price', 'wholesale_price', 'min_price',
        'min_stock', 'image_path', 'note', 'is_active',
    ];


    protected $casts = [
        'is_active' => 'boolean',
        'min_stock' => 'decimal:3',
        'stock' => 'decimal:3',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getStockStatusAttribute(): string
    {
        $stock = (float) $this->stock;

        return match (true) {
            $stock <= 0 => 'out',
            $stock <= (float) $this->min_stock => 'low',
            default => 'ok',
        };
    }

    public function scopeNeedingRestock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term = trim((string) $term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('barcode', $term)
                ->orWhere('sku', $term)
                ->orWhere('name', 'like', "%{$term}%");
        });
    }
}
