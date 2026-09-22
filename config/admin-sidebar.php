<?php

return [
    [
        'label' => 'Roles',
        'route' => 'roles.index',
        'icon' => 'shield',
        'active_pattern' => 'roles.*',
        'requires_permission' => 'roles.view',
    ],
    [
        'label' => 'Permissions',
        'route' => 'permissions.index',
        'icon' => 'key',
        'active_pattern' => 'permissions.*',
        'requires_permission' => 'permissions.view',
    ],
    [
        'label' => 'Horizon',
        'url' => '/horizon',
        'icon' => 'speed',
        'active_pattern' => 'never-matches',
    ],
    [
        'label' => 'Pulse',
        'url' => '/pulse',
        'icon' => 'monitoring',
        'active_pattern' => 'never-matches',
    ],
];
