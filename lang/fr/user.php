<?php

return [
    'add' => 'Ajouter un utilisateur',
    'edit' => 'Modifier l\'utilisateur',
    'last_login' => 'Dernière connexion :',
    'password_hint' => 'Laissez vide pour conserver le mot de passe actuel.',
    'disable' => 'Désactiver',
    'enable' => 'Activer',
    'disable_confirm' => 'Désactiver cet utilisateur ? Il ne pourra plus se connecter.',
    'enable_confirm' => 'Réactiver cet utilisateur ?',
    'not_yourself' => 'Vous ne pouvez pas désactiver votre propre compte.',
    'last_owner' => 'Il doit rester au moins un propriétaire.',
    'created' => ':name a été ajouté.',
    'updated' => ':name a été modifié.',
    'enabled' => ':name a été activé.',
    'disabled' => ':name a été désactivé.',

    'fields' => [
        'name' => 'Nom', 'email' => 'Email', 'phone' => 'Téléphone', 'role' => 'Rôle',
        'password' => 'Mot de passe', 'password_confirmation' => 'Confirmer le mot de passe',
        'locale' => 'Langue', 'is_active' => 'Compte actif',
    ],

    'roles' => [
        'owner' => 'Propriétaire', 'manager' => 'Gérant', 'sales' => 'Vendeur',
        'purchasing' => 'Acheteur', 'warehouse' => 'Magasinier', 'accountant' => 'Comptable',
    ],

    'role_hints' => [
        'owner' => 'Tous les droits, y compris utilisateurs et paramètres.',
        'manager' => 'Toutes les opérations et rapports, sans les paramètres.',
        'sales' => 'Ventes, clients et retours uniquement.',
        'purchasing' => 'Achats, fournisseurs et produits.',
        'warehouse' => 'Stock et inventaire uniquement.',
        'accountant' => 'Règlements, dépenses et rapports financiers.',
    ],
];
