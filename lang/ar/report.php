<?php

return [
    'export_pdf' => 'تصدير PDF',
    'print' => 'طباعة',
    'export' => 'تصدير Excel',
    'empty' => 'لا توجد بيانات في هذه الفترة.',
    'total' => 'المجموع',
    'none_allowed' => 'لا توجد تقارير متاحة لك.',

    'names' => [
        'sales_day' => 'المبيعات اليومية',
        'sales_product' => 'المبيعات حسب المنتج',
        'sales_employee' => 'المبيعات حسب البائع',
        'purchases_supplier' => 'المشتريات حسب المورّد',
        'inventory' => 'قيمة المخزون',
        'expenses' => 'المصاريف حسب النوع',
        'financial' => 'الملخص المالي',
    ],

    'hints' => [
        'sales_day' => 'رقم الأعمال والربح يوما بيوم.',
        'sales_product' => 'ما الذي يُباع فعلا، الأكثر أولا.',
        'sales_employee' => 'مبيعات كل بائع.',
        'purchases_supplier' => 'ما اشتريته من كل مورّد وما بقي عليك.',
        'inventory' => 'قيمة البضاعة الموجودة بسعر الشراء والبيع.',
        'expenses' => 'أين تذهب المصاريف.',
        'financial' => 'الإيرادات والمصاريف والربح الصافي.',
    ],

    'columns' => [
        'sales_day' => [
            'day' => 'اليوم', 'sales_count' => 'عدد الفواتير', 'revenue' => 'رقم الأعمال',
            'cost' => 'التكلفة', 'profit' => 'الربح', 'due' => 'غير مسدد',
        ],
        'sales_product' => [
            'product_name' => 'المنتج', 'quantity' => 'الكمية', 'revenue' => 'رقم الأعمال', 'profit' => 'الربح',
        ],
        'sales_employee' => [
            'employee' => 'البائع', 'sales_count' => 'عدد الفواتير', 'revenue' => 'رقم الأعمال', 'profit' => 'الربح',
        ],
        'purchases_supplier' => [
            'supplier' => 'المورّد', 'purchases_count' => 'عدد العمليات', 'total' => 'الإجمالي', 'due' => 'الباقي',
        ],
        'inventory' => [
            'product_name' => 'المنتج', 'quantity' => 'الكمية',
            'cost_value' => 'القيمة بسعر الشراء', 'retail_value' => 'القيمة بسعر البيع', 'status' => 'الحالة',
        ],
        'expenses' => ['category' => 'النوع', 'items' => 'العدد', 'total' => 'المجموع'],
        'financial' => [
            'revenue' => 'رقم الأعمال',
            'cost' => 'تكلفة البضاعة المباعة',
            'returns' => 'الإرجاعات',
            'gross_profit' => 'الربح الإجمالي',
            'expenses' => 'المصاريف',
            'net_profit' => 'الربح الصافي',
            'purchases' => 'المشتريات',
            'customer_debt' => 'ديون الزبائن',
            'supplier_debt' => 'ديون الموردين',
        ],
    ],
];
