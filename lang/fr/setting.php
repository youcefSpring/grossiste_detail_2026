<?php

return [
    'saved' => 'Paramètres enregistrés.',
    'shop' => 'Informations du magasin',
    'selling' => 'Paramètres de vente',
    'advanced' => 'Options avancées',
    'advanced_hint' => 'N\'activez que ce dont vous avez réellement besoin : chaque option ajoute des champs.',
    'max_discount_hint' => 'Remise maximale accordée par un vendeur sans autorisation.',
    'prefix_hint' => 'Apparaît dans le numéro de facture, ex. INV-2026-00001.',
    'payment_methods_hint' => 'Ce qui n\'est pas coché n\'apparaît pas au vendeur.',

    'fields' => [
        'shop_name' => 'Nom du magasin', 'shop_phone' => 'Téléphone', 'shop_address' => 'Adresse',
        'logo' => 'Logo (imprimé sur la facture)', 'default_type' => 'Type de vente par défaut',
        'max_discount' => 'Plafond de remise vendeur', 'payment_methods' => 'Modes de paiement disponibles',
        'currency_symbol' => 'Symbole monétaire', 'invoice_prefix' => 'Préfixe de facture',
        'default_locale' => 'Langue par défaut',
    ],

    'toggles' => [
        'tax_enabled' => 'Activer la TVA',
        'variants_enabled' => 'Activer les variantes (taille, couleur...)',
        'multi_warehouse_enabled' => 'Activer les dépôts multiples',
        'allow_negative_stock' => 'Autoriser la vente sans stock',
    ],

    'toggle_hints' => [
        'tax_enabled' => 'Ajoute le champ TVA aux produits et aux factures.',
        'variants_enabled' => 'Un produit peut avoir plusieurs tailles ou couleurs.',
        'multi_warehouse_enabled' => 'Affiche le choix du dépôt sur chaque opération.',
        'allow_negative_stock' => 'Risqué : permet de vendre même à stock zéro.',
    ],
];
