<?php

return [
    [
        'label' => 'Dashboard',
        'route' => 'admin.dashboard',
        'icon' => 'dashboard',
        'active_pattern' => 'admin.dashboard',
    ],
    [
        'label' => 'Payment Gateways',
        'route' => 'gateways.index',
        'icon' => 'payment',
        'active_pattern' => 'gateways.*',
    ],
    [
        'label' => 'Pricing Tiers',
        'route' => 'admin.pricing-tiers.index',
        'icon' => 'local_offer',
        'active_pattern' => 'admin.pricing-tiers.*',
        'requires_permission' => 'pricing-tiers.view',
    ],
];
