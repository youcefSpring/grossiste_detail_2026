<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $today = Sale::whereDate('sold_at', today())->where('status', 'completed');

        $cards = [];

        if ($user->can('sale.view')) {
            $cards[] = ['key' => 'today_sales', 'value' => (int) (clone $today)->sum('total')];
        }

        if ($user->can('purchase.view')) {
            $cards[] = ['key' => 'today_purchases', 'value' => (int) Purchase::whereDate('purchased_at', today())
                ->where('status', 'received')->sum('total')];
        }

        // Profit exposes margins, so it follows the same right as the financial reports.
        if ($user->can('report.financial')) {
            $cards[] = ['key' => 'today_profit',
                'value' => (int) ((clone $today)->sum('total') - (clone $today)->sum('cost_total'))];
        }

        if ($user->can('customer.view')) {
            $cards[] = ['key' => 'customer_debts', 'value' => (int) Customer::where('balance', '>', 0)->sum('balance')];
        }

        if ($user->can('supplier.view')) {
            $cards[] = ['key' => 'supplier_debts', 'value' => (int) Supplier::where('balance', '>', 0)->sum('balance')];
        }

        // Both panels carry figures of their own — what we stock, who bought
        // what for how much — so each needs the right that guards that screen.
        $lowStock = $user->can('stock.view')
            ? Product::query()
                ->where('is_active', true)
                ->needingRestock()
                ->orderBy('stock')
                ->limit(8)
                ->get()
            : collect();

        $recentSales = $user->can('sale.view')
            ? Sale::with('customer:id,name')
                ->where('status', 'completed')
                ->latest('sold_at')
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        return view('dashboard', compact('cards', 'lowStock', 'recentSales'));
    }
}
