<?php

use App\Models\PaymentGatewayType;
use App\Services\PaymentGatewayRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::clear();
});

it('loads active gateway types from database', function () {
    PaymentGatewayType::factory()->create(['name' => 'midtrans', 'is_active' => true]);
    PaymentGatewayType::factory()->create(['name' => 'xendit', 'is_active' => false]);

    $registry = new PaymentGatewayRegistry;
    $types = $registry->getGatewayTypes();

    expect($types)->toHaveCount(1);
    expect($types->first()->name)->toBe('midtrans');
});

it('caches gateway types for performance', function () {
    PaymentGatewayType::factory()->create(['name' => 'midtrans', 'is_active' => true]);

    $registry = new PaymentGatewayRegistry;

    $types1 = $registry->getGatewayTypes();
    PaymentGatewayType::factory()->create(['name' => 'xendit', 'is_active' => true]);

    $types2 = $registry->getGatewayTypes();

    expect($types1)->toHaveCount(1);
    expect($types2)->toHaveCount(1);
});

it('can clear cache', function () {
    PaymentGatewayType::factory()->create(['name' => 'midtrans', 'is_active' => true]);

    $registry = new PaymentGatewayRegistry;

    $types1 = $registry->getGatewayTypes();
    expect($types1)->toHaveCount(1);

    PaymentGatewayType::factory()->create(['name' => 'xendit', 'is_active' => true]);
    $registry->clearCache();

    $types2 = $registry->getGatewayTypes();
    expect($types2)->toHaveCount(2);
});
