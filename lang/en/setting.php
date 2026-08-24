<?php

return [
    'saved' => 'Settings saved.',
    'shop' => 'Shop details',
    'selling' => 'Selling defaults',
    'advanced' => 'Advanced options',
    'advanced_hint' => 'Only turn on what you genuinely need — each option adds fields to the screens.',
    'max_discount_hint' => 'Largest discount a cashier may give without approval.',
    'prefix_hint' => 'Appears in the invoice number, e.g. INV-2026-00001.',
    'payment_methods_hint' => 'Anything unchecked never appears to the cashier.',

    'fields' => [
        'shop_name' => 'Shop name', 'shop_phone' => 'Phone', 'shop_address' => 'Address',
        'logo' => 'Logo (printed on invoices)', 'default_type' => 'Default sale type',
        'max_discount' => 'Cashier discount ceiling', 'payment_methods' => 'Available payment methods',
        'currency_symbol' => 'Currency symbol', 'invoice_prefix' => 'Invoice prefix',
        'default_locale' => 'Default language',
    ],

    'toggles' => [
        'tax_enabled' => 'Enable VAT',
        'variants_enabled' => 'Enable product variants (size, colour...)',
        'multi_warehouse_enabled' => 'Enable multiple warehouses',
        'allow_negative_stock' => 'Allow selling without stock',
    ],

    'toggle_hints' => [
        'tax_enabled' => 'Adds a tax field to products and invoices.',
        'variants_enabled' => 'One product can have several sizes or colours.',
        'multi_warehouse_enabled' => 'Shows a warehouse picker on every operation.',
        'allow_negative_stock' => 'Risky: lets you sell even at zero stock.',
    ],
];
