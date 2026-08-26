<?php

return [
    'search_hint' => 'Name, email or phone',
    'none' => 'No user matches.',
    'add' => 'Add user',
    'edit' => 'Edit user',
    'last_login' => 'Last login:',
    'password_hint' => 'Leave empty to keep the current password.',
    'disable' => 'Disable',
    'enable' => 'Enable',
    'disable_confirm' => 'Disable this user? They will not be able to log in.',
    'enable_confirm' => 'Re-enable this user?',
    'not_yourself' => 'You cannot disable your own account.',
    'last_owner' => 'At least one owner must remain.',
    'created' => ':name was added.',
    'updated' => ':name was updated.',
    'enabled' => ':name was enabled.',
    'disabled' => ':name was disabled.',

    'fields' => [
        'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'role' => 'Role',
        'password' => 'Password', 'password_confirmation' => 'Confirm password',
        'locale' => 'Language', 'is_active' => 'Active account',
    ],

    'roles' => [
        'owner' => 'Owner', 'manager' => 'Manager', 'sales' => 'Cashier',
        'purchasing' => 'Buyer', 'warehouse' => 'Stock keeper', 'accountant' => 'Accountant',
    ],

    'role_hints' => [
        'owner' => 'Everything, including users and settings.',
        'manager' => 'All operations and reports, but not settings.',
        'sales' => 'Selling, customers and returns only.',
        'purchasing' => 'Purchases, suppliers and products.',
        'warehouse' => 'Stock and stock counts only.',
        'accountant' => 'Payments, expenses and financial reports.',
    ],
    'deleted' => ':name deleted.',
    'delete_confirm' => 'Delete this user?',
];
