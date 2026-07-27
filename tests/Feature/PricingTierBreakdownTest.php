<?php

use App\Models\PricingTier;
use Database\Seeders\PricingTierSeeder;

test('it returns the vat and admin fee breakdown for an active tier', function () {
    $this->seed(PricingTierSeeder::class);

    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();

    $response = $this->getJson(route('pricing-tiers.breakdown', $plusTier));

    $subtotal = (float) $plusTier->price;
    $vatAmount = $subtotal * config('billing.vat_rate');
    $adminFeeAmount = $subtotal * config('billing.admin_fee_rate');

    $response->assertOk();

    expect($response->json())
        ->id->toBe($plusTier->id)
        ->name->toBe($plusTier->name)
        ->subtotal->toEqual($subtotal)
        ->vat_rate->toEqual(config('billing.vat_rate'))
        ->admin_fee_rate->toEqual(config('billing.admin_fee_rate'))
        ->vat_amount->toEqual($vatAmount)
        ->admin_fee_amount->toEqual($adminFeeAmount)
        ->total->toEqual($subtotal + $vatAmount + $adminFeeAmount);
});

test('it 404s for an inactive tier', function () {
    $tier = PricingTier::factory()->create(['is_active' => false]);

    $this->getJson(route('pricing-tiers.breakdown', $tier))->assertNotFound();
});
