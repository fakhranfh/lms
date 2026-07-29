<?php

use App\Models\PaymentChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logo url is rebuilt from the stored key using current r2 config', function () {
    $channel = PaymentChannel::create([
        'code' => 'TEST_KEY',
        'label' => 'Test',
        'brand_color' => '#000000',
        'logo_url' => 'channel-logos/TEST_KEY',
    ]);

    expect($channel->logo_url)->toBe(config('services.r2.custom_domain').'/channel-logos/TEST_KEY');
});

test('logo url still works when a legacy full url is stored', function () {
    $channel = PaymentChannel::create([
        'code' => 'TEST_LEGACY',
        'label' => 'Test',
        'brand_color' => '#000000',
        'logo_url' => 'https://old-domain.example.com/channel-logos/TEST_LEGACY',
    ]);

    expect($channel->logo_url)->toBe(config('services.r2.custom_domain').'/channel-logos/TEST_LEGACY');
});
