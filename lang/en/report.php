<?php

return [
    'export_pdf' => 'Export to PDF',
    'print' => 'Print',
    'export' => 'Export to Excel',
    'empty' => 'No data in this period.',
    'total' => 'Total',
    'none_allowed' => 'No reports available to you.',

    'names' => [
        'sales_day' => 'Sales by day',
        'sales_product' => 'Sales by product',
        'sales_employee' => 'Sales by employee',
        'purchases_supplier' => 'Purchases by supplier',
        'inventory' => 'Stock value',
        'expenses' => 'Expenses by category',
        'financial' => 'Financial summary',
    ],

    'hints' => [
        'sales_day' => 'Revenue and profit, day by day.',
        'sales_product' => 'What actually sells, best first.',
        'sales_employee' => 'Each employee\'s sales.',
        'purchases_supplier' => 'What you bought and still owe, per supplier.',
        'inventory' => 'Stock value at cost and at retail.',
        'expenses' => 'Where the money goes.',
        'financial' => 'Revenue, expenses and net profit.',
    ],

    'columns' => [
        'sales_day' => [
            'day' => 'Day', 'sales_count' => 'Invoices', 'revenue' => 'Revenue',
            'cost' => 'Cost', 'profit' => 'Profit', 'due' => 'Unpaid',
        ],
        'sales_product' => [
            'product_name' => 'Product', 'quantity' => 'Quantity', 'revenue' => 'Revenue', 'profit' => 'Profit',
        ],
        'sales_employee' => [
            'employee' => 'Employee', 'sales_count' => 'Invoices', 'revenue' => 'Revenue', 'profit' => 'Profit',
        ],
        'purchases_supplier' => [
            'supplier' => 'Supplier', 'purchases_count' => 'Purchases', 'total' => 'Total', 'due' => 'Remaining',
        ],
        'inventory' => [
            'product_name' => 'Product', 'quantity' => 'Quantity',
            'cost_value' => 'Value at cost', 'retail_value' => 'Value at retail', 'status' => 'Status',
        ],
        'expenses' => ['category' => 'Category', 'items' => 'Count', 'total' => 'Total'],
        'financial' => [
            'revenue' => 'Revenue',
            'cost' => 'Cost of goods sold',
            'returns' => 'Returns',
            'gross_profit' => 'Gross profit',
            'expenses' => 'Expenses',
            'net_profit' => 'Net profit',
            'purchases' => 'Purchases',
            'customer_debt' => 'Customer debt',
            'supplier_debt' => 'Supplier debt',
        ],
    ],
];
