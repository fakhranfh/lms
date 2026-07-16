<?php

return [
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon' => 'dashboard',
        'active_pattern' => 'dashboard',
    ],
    [
        'label' => 'Courses',
        'route' => 'courses.index',
        'icon' => 'school',
        'active_pattern' => 'courses.*',
        'requires_permission' => 'courses.view',
        'requires_school' => true,
        'exclude_role' => 'Admin',
    ],
    [
        'label' => 'Users',
        'route' => 'users.index',
        'icon' => 'group',
        'active_pattern' => 'users.*',
        'requires_permission' => 'users.view',
    ],
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
        'label' => 'Tier Management',
        'route' => 'tier-management.show',
        'icon' => 'layers',
        'active_pattern' => 'tier-management.*',
        'requires_school' => true,
    ],
];
