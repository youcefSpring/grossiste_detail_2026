<?php

return [
    'export_pdf' => 'Export PDF',
    'print' => 'Imprimer',
    'export' => 'Export Excel',
    'empty' => 'Aucune donnée sur cette période.',
    'total' => 'Total',
    'none_allowed' => 'Aucun rapport disponible.',

    'names' => [
        'sales_day' => 'Ventes par jour',
        'sales_product' => 'Ventes par produit',
        'sales_employee' => 'Ventes par vendeur',
        'purchases_supplier' => 'Achats par fournisseur',
        'inventory' => 'Valeur du stock',
        'expenses' => 'Dépenses par catégorie',
        'financial' => 'Résumé financier',
    ],

    'hints' => [
        'sales_day' => 'Chiffre d\'affaires et bénéfice jour par jour.',
        'sales_product' => 'Ce qui se vend vraiment, les meilleurs d\'abord.',
        'sales_employee' => 'Les ventes de chaque vendeur.',
        'purchases_supplier' => 'Achats et restes à payer par fournisseur.',
        'inventory' => 'Valeur du stock au prix d\'achat et de vente.',
        'expenses' => 'Où part l\'argent.',
        'financial' => 'Recettes, dépenses et bénéfice net.',
    ],

    'columns' => [
        'sales_day' => [
            'day' => 'Jour', 'sales_count' => 'Factures', 'revenue' => 'CA',
            'cost' => 'Coût', 'profit' => 'Bénéfice', 'due' => 'Impayé',
        ],
        'sales_product' => [
            'product_name' => 'Produit', 'quantity' => 'Quantité', 'revenue' => 'CA', 'profit' => 'Bénéfice',
        ],
        'sales_employee' => [
            'employee' => 'Vendeur', 'sales_count' => 'Factures', 'revenue' => 'CA', 'profit' => 'Bénéfice',
        ],
        'purchases_supplier' => [
            'supplier' => 'Fournisseur', 'purchases_count' => 'Achats', 'total' => 'Total', 'due' => 'Reste',
        ],
        'inventory' => [
            'product_name' => 'Produit', 'quantity' => 'Quantité',
            'cost_value' => 'Valeur (achat)', 'retail_value' => 'Valeur (vente)', 'status' => 'État',
        ],
        'expenses' => ['category' => 'Catégorie', 'items' => 'Nombre', 'total' => 'Total'],
        'financial' => [
            'revenue' => 'Chiffre d\'affaires',
            'cost' => 'Coût des marchandises vendues',
            'returns' => 'Retours',
            'gross_profit' => 'Marge brute',
            'expenses' => 'Dépenses',
            'net_profit' => 'Bénéfice net',
            'purchases' => 'Achats',
            'customer_debt' => 'Créances clients',
            'supplier_debt' => 'Dettes fournisseurs',
        ],
    ],
];
