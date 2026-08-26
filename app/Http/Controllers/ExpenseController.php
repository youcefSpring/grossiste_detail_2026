<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $query = Expense::query()
            ->with('category:id,name', 'user:id,name')
            ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('expense_category_id'),
                fn ($q) => $q->where('expense_category_id', $request->input('expense_category_id')));

        return view('expenses.index', [
            'expenses' => $query->clone()->latest('spent_at')->latest('id')->paginate(per_page())->withQueryString(),
            'total' => (int) $query->clone()->sum('amount'),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create()
    {
        return view('expenses.form', [
            'expense' => new Expense(['spent_at' => now(), 'method' => 'cash']),
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseRequest $request)
    {
        $data = $request->expenseData();
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('expenses', 'public');
        }

        Expense::create($data);

        return $this->done(__('expense.created'), route('expenses.index'));
    }

    public function edit(Expense $expense)
    {
        return view('expenses.form', [
            'expense' => $expense,
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $data = $request->expenseData();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('expenses', 'public');
        }

        $expense->update($data);

        return $this->done(__('expense.updated'), route('expenses.index'));
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return $this->done(__('expense.deleted'), route('expenses.index'));
    }
}
