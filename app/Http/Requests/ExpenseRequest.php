<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense.manage');
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'spent_at' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return __('expense.fields');
    }

    public function expenseData(): array
    {
        return [
            'expense_category_id' => $this->input('expense_category_id') ?: null,
            'amount' => centimes($this->input('amount')),
            'method' => $this->input('method'),
            'spent_at' => $this->input('spent_at'),
            'description' => $this->input('description') ?: null,
        ];
    }
}
