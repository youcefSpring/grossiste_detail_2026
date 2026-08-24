<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sale.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'type' => ['required', Rule::in(['retail', 'wholesale'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return __('sale.fields');
    }

    /**
     * A cashier without the unlimited-discount right cannot exceed the configured
     * ceiling, whatever the browser sent.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user()->can('sale.discount.unlimited')) {
                return;
            }

            $subtotal = collect($this->input('items', []))
                ->sum(fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));

            $maxPercent = (float) settings('sale.max_discount_percent', 0);
            $discount = (float) $this->input('discount_amount', 0);

            if ($subtotal > 0 && $discount > $subtotal * $maxPercent / 100) {
                $validator->errors()->add('discount_amount', __('sale.discount_too_big', ['percent' => $maxPercent]));
            }
        });
    }

    public function saleData(): array
    {
        return [
            'customer_id' => $this->input('customer_id') ?: null,
            'type' => $this->input('type'),
            'discount_amount' => centimes($this->input('discount_amount') ?: 0),
            'paid_amount' => centimes($this->input('paid_amount') ?: 0),
            'method' => $this->input('method'),
            'note' => $this->input('note') ?: null,
            'items' => collect($this->input('items'))
                ->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => centimes($item['unit_price']),
                ])
                ->all(),
        ];
    }
}
