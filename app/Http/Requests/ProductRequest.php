<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('product.manage');
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:190'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($id)->whereNull('deleted_at')],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($id)->whereNull('deleted_at')],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'retail_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'wholesale_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'stock' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return __('product.fields');
    }

    /** Prices arrive as decimals from the form; the DB keeps centimes. */
    public function productData(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'category_id' => $this->input('category_id') ?: null,
            'barcode' => $this->input('barcode') ?: null,
            'sku' => $this->input('sku') ?: null,
            'unit' => $this->input('unit'),
            'cost_price' => centimes($this->input('cost_price')),
            'retail_price' => centimes($this->input('retail_price')),
            'wholesale_price' => centimes($this->input('wholesale_price')),
            'min_price' => centimes($this->input('min_price') ?: 0),
            'min_stock' => $this->float('min_stock'),
            'note' => $this->input('note') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
