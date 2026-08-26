<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $fillable = [
        'expense_category_id', 'user_id', 'amount', 'method',
        'spent_at', 'description', 'attachment_path',
    ];

    protected $casts = ['spent_at' => 'date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
