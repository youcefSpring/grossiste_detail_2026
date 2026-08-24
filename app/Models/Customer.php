<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'address', 'is_wholesale', 'credit_limit', 'note', 'is_active',
    ];

    protected $casts = ['is_wholesale' => 'boolean', 'is_active' => 'boolean'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'party_id')->where('party_type', 'customer');
    }

    /** 0 means no limit at all. */
    public function creditLeft(): ?int
    {
        return $this->credit_limit > 0 ? $this->credit_limit - $this->balance : null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term = trim((string) $term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%"));
    }
}
