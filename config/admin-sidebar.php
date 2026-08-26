<?php

return [
    [
        'label' => 'Dashboard',
        'route' => 'admin.dashboard',
        'icon' => 'dashboard',
        'active_pattern' => 'admin.dashboard',
    ],
    [
        'label' => 'Users',
        'route' => 'admin.users.index',
        'icon' => 'group',
        'active_pattern' => 'admin.users.*',
        'requires_permission' => 'users.view',
    ],
    [
        'label' => 'Roles',
        'route' => 'admin.roles.index',
        'icon' => 'shield',
        'active_pattern' => 'admin.roles.*',
        'requires_permission' => 'roles.view',
    ],
    [
        'label' => 'Permissions',
        'route' => 'admin.permissions.index',
        'icon' => 'key',
        'active_pattern' => 'admin.permissions.*',
        'requires_permission' => 'permissions.view',
    ],
    [
        'label' => 'Payment Gateways',
        'route' => 'admin.gateways.index',
        'icon' => 'payment',
        'active_pattern' => 'admin.gateways.*',
    ],
    [
        'label' => 'Pricing Tiers',
        'route' => 'admin.pricing-tiers.index',
        'icon' => 'local_offer',
        'active_pattern' => 'admin.pricing-tiers.*',
        'requires_permission' => 'pricing-tiers.view',
    ],
    [
        'label' => 'Schools',
        'route' => 'admin.schools.index',
        'icon' => 'school',
        'active_pattern' => 'admin.schools.*',
    ],
    [
        'label' => 'Transactions',
        'icon' => 'receipt_long',
        'active_pattern' => 'admin.transactions.*',
        'children' => [
            [
                'label' => 'Summary',
                'route' => 'admin.transactions.summary',
                'active_pattern' => 'admin.transactions.summary*',
            ],
            [
                'label' => 'List Transactions',
                'route' => 'admin.transactions.index',
                'active_pattern' => ['admin.transactions.index*', 'admin.transactions.show*'],
            ],
        ],
    ],
    [
        'label' => 'Storage',
        'route' => 'admin.storage.dashboard',
        'icon' => 'cloud',
        'active_pattern' => 'admin.storage.*',
        'requires_permission' => 'analytics.view',
    ],
    [
        'label' => 'Demo Credentials',
        'route' => 'admin.demo-credentials',
        'icon' => 'preview',
        'active_pattern' => 'admin.demo-credentials',
    ],
    [
        'label' => 'Settings',
        'route' => 'admin.settings.index',
        'icon' => 'settings',
        'active_pattern' => 'admin.settings.*',
        'requires_permission' => 'settings.view',
    ],
    [
        'label' => 'Audit Logs',
        'route' => 'admin.audit-logs.index',
        'icon' => 'history',
        'active_pattern' => 'admin.audit-logs.*',
    ],
    [
        'label' => 'Cache',
        'route' => 'admin.cache.index',
        'icon' => 'memory',
        'active_pattern' => 'admin.cache.*',
        'local_only' => true,
    ],
    [
        'label' => 'Horizon',
        'url' => '/horizon',
        'route' => 'admin.dashboard',
        'icon' => 'speed',
        'active_pattern' => 'never-matches',
    ],
    [
        'label' => 'Pulse',
        'url' => '/pulse',
        'route' => 'admin.dashboard',
        'icon' => 'monitoring',
        'active_pattern' => 'never-matches',
    ],
];
