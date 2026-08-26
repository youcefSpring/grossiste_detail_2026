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
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
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

    /**
     * With no supplier there is no account to carry what is left owing, so a
     * purchase without one has to be paid in full.
     */
    public function after(): array
    {
        return [function ($validator) {
            if ($this->filled('supplier_id') || $validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $this->purchaseData();
            $subtotal = array_sum(array_map(
                fn ($item) => (int) round($item['quantity'] * $item['unit_cost']),
                $data['items'],
            ));
            $total = $subtotal - min($data['discount_amount'], $subtotal);

            if ($data['paid_amount'] < $total) {
                $validator->errors()->add('supplier_id', __('purchase.supplier_needed_for_credit'));
            }
        }];
    }

    public function attributes(): array
    {
        return __('purchase.fields');
    }

    /** Form sends decimals; the service works in centimes. */
    public function purchaseData(): array
    {
        return [
            'supplier_id' => $this->input('supplier_id') ?: null,
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
