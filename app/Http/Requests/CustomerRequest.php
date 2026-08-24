<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customer.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_wholesale' => ['boolean'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return __('customer.fields');
    }

    public function customerData(): array
    {
        return [
            ...$this->safe()->except('credit_limit'),
            'credit_limit' => centimes($this->input('credit_limit') ?: 0),
        ];
    }
}
