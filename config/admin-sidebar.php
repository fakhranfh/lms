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
        'route' => 'admin.settings',
        'icon' => 'settings',
        'active_pattern' => 'admin.settings',
    ],
    [
        'label' => 'Logs',
        'route' => 'admin.logs',
        'icon' => 'description',
        'active_pattern' => 'admin.logs',
    ],
];
