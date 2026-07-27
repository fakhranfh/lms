<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierSubscriptionDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TierSubscriptionDetail>
 */
class TierSubscriptionDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_transaction_id' => PaymentTransaction::factory(),
            'school_id' => School::factory(),
            'subscription_id' => SchoolTier::factory(),
        ];
    }
}
