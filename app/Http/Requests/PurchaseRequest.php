<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase.create');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchased_at' => ['required', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return __('purchase.fields');
    }

    /** Form sends decimals; the service works in centimes. */
    public function purchaseData(): array
    {
        return [
            'supplier_id' => (int) $this->input('supplier_id'),
            'purchased_at' => $this->input('purchased_at'),
            'discount_amount' => centimes($this->input('discount_amount') ?: 0),
            'paid_amount' => centimes($this->input('paid_amount') ?: 0),
            'method' => $this->input('method'),
            'note' => $this->input('note') ?: null,
            'items' => collect($this->input('items'))
                ->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_cost' => centimes($item['unit_cost']),
                ])
                ->all(),
        ];
    }
}
