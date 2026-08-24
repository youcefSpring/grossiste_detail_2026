<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;

/**
 * The short list of things a shop owner would want poked about.
 * Computed on demand and cached for a minute — no notifications table to maintain.
 */
class Alerts
{
    public static function for($user): array
    {
        if (! $user) {
            return [];
        }

        return cache()->remember("alerts.{$user->id}", now()->addMinute(), function () use ($user) {
            $alerts = [];

            if ($user->can('stock.view')) {
                $out = Product::where('is_active', true)->outOfStock()->count();
                $low = Product::where('is_active', true)->lowStock()->count();

                if ($out) {
                    $alerts[] = ['key' => 'out_of_stock', 'count' => $out, 'tone' => 'red',
                        'url' => route('inventory.index', ['status' => 'out'])];
                }

                if ($low) {
                    $alerts[] = ['key' => 'low_stock', 'count' => $low, 'tone' => 'amber',
                        'url' => route('inventory.index', ['status' => 'low'])];
                }
            }

            if ($user->can('customer.view')) {
                $debtors = Customer::where('balance', '>', 0)->count();

                if ($debtors) {
                    $alerts[] = ['key' => 'customer_debts', 'count' => $debtors, 'tone' => 'amber',
                        'url' => route('customers.index', ['status' => 'debt'])];
                }

                // Anyone who has hit their ceiling cannot buy on credit any more.
                $atLimit = Customer::where('credit_limit', '>', 0)
                    ->whereColumn('balance', '>=', 'credit_limit')->count();

                if ($atLimit) {
                    $alerts[] = ['key' => 'credit_limit', 'count' => $atLimit, 'tone' => 'red',
                        'url' => route('customers.index', ['status' => 'debt'])];
                }
            }

            if ($user->can('purchase.view')) {
                $unpaid = Purchase::where('due_amount', '>', 0)->count();

                if ($unpaid) {
                    $alerts[] = ['key' => 'supplier_debts', 'count' => $unpaid, 'tone' => 'amber',
                        'url' => route('purchases.index', ['status' => 'due'])];
                }
            }

            if ($user->can('sale.view')) {
                $unpaidSales = Sale::where('due_amount', '>', 0)->where('status', '!=', 'voided')->count();

                if ($unpaidSales) {
                    $alerts[] = ['key' => 'unpaid_sales', 'count' => $unpaidSales, 'tone' => 'slate',
                        'url' => route('sales.index', ['status' => 'due'])];
                }
            }

            return $alerts;
        });
    }

    public static function forget($user): void
    {
        cache()->forget("alerts.{$user->id}");
    }
}
