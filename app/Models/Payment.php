<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use RecordsActivity;

    /** Algerian shops run on cash and credit; the rest stay hidden until enabled. */
    public const METHODS = ['cash', 'credit', 'transfer', 'cheque', 'card', 'exchange'];

    protected $fillable = [
        'direction', 'party_type', 'party_id', 'payable_type', 'payable_id',
        'amount', 'method', 'reference', 'user_id', 'paid_at', 'note',
    ];

    protected $casts = ['paid_at' => 'date'];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }
}
