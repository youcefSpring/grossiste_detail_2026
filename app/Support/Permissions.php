<?php

namespace App\Support;

class Permissions
{
    /** Flat, readable ability list. Groups are UI-only. */
    public const ALL = [
        'sale.view', 'sale.create', 'sale.discount.unlimited', 'sale.void', 'sale.return', 'sale.exchange',
        'purchase.view', 'purchase.create', 'purchase.return', 'purchase.void',
        'product.view', 'product.manage', 'product.cost.view',
        'stock.view', 'stock.receive', 'stock.transfer', 'stock.adjust',
        'customer.view', 'customer.manage',
        'supplier.view', 'supplier.manage',
        'payment.record',
        'expense.view', 'expense.manage',
        'report.sales', 'report.inventory', 'report.financial',
        'user.manage', 'settings.manage', 'audit.view',
    ];

    public const ROLES = [
        'owner' => ['*'],
        'manager' => [
            'sale.view', 'sale.create', 'sale.discount.unlimited', 'sale.void', 'sale.return', 'sale.exchange',
            'purchase.view', 'purchase.create', 'purchase.return', 'purchase.void',
            'product.view', 'product.manage', 'product.cost.view',
            'stock.view', 'stock.receive', 'stock.transfer', 'stock.adjust',
            'customer.view', 'customer.manage', 'supplier.view', 'supplier.manage',
            'payment.record', 'expense.view', 'expense.manage',
            'report.sales', 'report.inventory', 'report.financial', 'audit.view',
        ],
        'sales' => [
            'sale.view', 'sale.create', 'sale.return', 'sale.exchange',
            'product.view', 'stock.view',
            'customer.view', 'customer.manage', 'payment.record', 'report.sales',
        ],
        'purchasing' => [
            'purchase.view', 'purchase.create', 'purchase.return',
            'product.view', 'product.manage', 'product.cost.view',
            'stock.view', 'stock.receive',
            'supplier.view', 'supplier.manage', 'payment.record', 'report.inventory',
        ],
        'warehouse' => [
            'product.view', 'stock.view', 'stock.receive', 'stock.transfer', 'stock.adjust', 'report.inventory',
        ],
        'accountant' => [
            'sale.view', 'purchase.view', 'product.view', 'product.cost.view',
            'customer.view', 'supplier.view', 'payment.record',
            'expense.view', 'expense.manage',
            'report.sales', 'report.inventory', 'report.financial',
        ],
    ];
}
