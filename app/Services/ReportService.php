<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregates. Every figure is centimes; every query is bounded by a date range.
 */
class ReportService
{
    /** Integer columns that are plain counts, not centimes. */
    public const COUNT_COLUMNS = ['sales_count', 'purchases_count', 'items'];

    /** Sales per day plus the running profit. */
    public function salesByDay(Carbon $from, Carbon $to): Collection
    {
        return Sale::query()
            ->selectRaw('date(sold_at) as day, count(*) as sales_count,
                         sum(total) as revenue, sum(cost_total) as cost, sum(due_amount) as due')
            ->where('status', '!=', 'voided')
            ->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('day')
            ->orderByDesc('day')
            ->get()
            ->map(fn ($row) => (object) [
                'day' => $row->day,
                'sales_count' => (int) $row->sales_count,
                'revenue' => (int) $row->revenue,
                'cost' => (int) $row->cost,
                'profit' => (int) $row->revenue - (int) $row->cost,
                'due' => (int) $row->due,
            ]);
    }

    /** What actually sells, best first. Returned quantities are already netted off. */
    public function salesByProduct(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw('sale_items.product_id, sale_items.product_name,
                         sum(sale_items.quantity - sale_items.returned_quantity) as quantity,
                         sum((sale_items.quantity - sale_items.returned_quantity) * sale_items.unit_price) as revenue,
                         sum((sale_items.quantity - sale_items.returned_quantity) * sale_items.unit_cost) as cost')
            ->where('sales.status', '!=', 'voided')
            ->whereBetween('sales.sold_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->havingRaw('quantity > 0')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => (object) [
                'product_name' => $row->product_name,
                'quantity' => (float) $row->quantity,
                'revenue' => (int) $row->revenue,
                'profit' => (int) $row->revenue - (int) $row->cost,
            ]);
    }

    /** Who sold what — the figure a shop owner checks on payday. */
    public function salesByEmployee(Carbon $from, Carbon $to): Collection
    {
        return Sale::query()
            ->selectRaw('users.name as employee, count(*) as sales_count, sum(sales.total) as revenue,
                         sum(sales.total - sales.cost_total) as profit')
            ->leftJoin('users', 'users.id', '=', 'sales.user_id')
            ->where('sales.status', '!=', 'voided')
            ->whereBetween('sales.sold_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => (object) [
                'employee' => $row->employee ?? '—',
                'sales_count' => (int) $row->sales_count,
                'revenue' => (int) $row->revenue,
                'profit' => (int) $row->profit,
            ]);
    }

    public function purchasesBySupplier(Carbon $from, Carbon $to): Collection
    {
        return Purchase::query()
            ->selectRaw('suppliers.name as supplier, count(*) as purchases_count,
                         sum(purchases.total) as total, sum(purchases.due_amount) as due')
            // Left join: a cash purchase with no supplier still belongs in the total.
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.status', '!=', 'voided')
            ->whereBetween('purchases.purchased_at', [$from->toDateString(), $to->toDateString()])
            ->groupBy('suppliers.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => (object) [
                'supplier' => $row->supplier ?? '/',
                'purchases_count' => (int) $row->purchases_count,
                'total' => (int) $row->total,
                'due' => (int) $row->due,
            ]);
    }

    /**
     * What the shelves are worth at cost, and what they would fetch.
     * Computed in SQL and returned as a query so a large catalogue can be paged or streamed
     * rather than materialised in memory.
     */
    public function inventoryValuation(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', '!=', 0)
            ->orderBy('name')
            ->select('products.name as product_name', 'products.stock as quantity')
            ->selectRaw('round(products.stock * products.cost_price) as cost_value')
            ->selectRaw('round(products.stock * products.retail_price) as retail_value')
            ->selectRaw("case when products.stock <= 0 then 'out'
                              when products.stock <= products.min_stock then 'low'
                              else 'ok' end as status");
    }

    /** Grand totals for the valuation, so a paged table can still show the real bottom line. */
    public function inventoryTotals(): object
    {
        $row = Product::query()
            ->where('is_active', true)
            ->where('stock', '!=', 0)
            ->selectRaw('sum(stock) as quantity,
                         sum(stock * cost_price) as cost_value,
                         sum(stock * retail_price) as retail_value')
            ->first();

        return (object) [
            'quantity' => (float) $row->quantity,
            'cost_value' => (int) $row->cost_value,
            'retail_value' => (int) $row->retail_value,
        ];
    }

    public function expensesByCategory(Carbon $from, Carbon $to): Collection
    {
        return Expense::query()
            ->selectRaw('expense_categories.name as category, count(*) as items, sum(expenses.amount) as total')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.spent_at', [$from->toDateString(), $to->toDateString()])
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => (object) [
                'category' => $row->category ?? '—',
                'items' => (int) $row->items,
                'total' => (int) $row->total,
            ]);
    }

    /** The bottom line: what came in, what went out, what is left owing. */
    public function financialSummary(Carbon $from, Carbon $to): object
    {
        $sales = Sale::where('status', '!=', 'voided')
            ->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);

        $revenue = (int) $sales->clone()->sum('total');
        $cost = (int) $sales->clone()->sum('cost_total');

        $returns = (int) DB::table('sale_returns')
            ->whereBetween('returned_at', [$from->startOfDay(), $to->endOfDay()])
            ->where('refund_method', '!=', 'exchange')
            ->sum('total_amount');

        $expenses = (int) Expense::whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])->sum('amount');

        $grossProfit = $revenue - $cost - $returns;

        return (object) [
            'revenue' => $revenue,
            'cost' => $cost,
            'returns' => $returns,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $grossProfit - $expenses,
            'purchases' => (int) Purchase::where('status', '!=', 'voided')
                ->whereBetween('purchased_at', [$from->toDateString(), $to->toDateString()])->sum('total'),
            'customer_debt' => (int) Customer::where('balance', '>', 0)->sum('balance'),
            'supplier_debt' => (int) Supplier::where('balance', '>', 0)->sum('balance'),
        ];
    }
}
