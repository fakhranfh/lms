<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\SchoolTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'subscription_id' => SchoolTier::factory(),
            'payment_gateway_id' => PaymentGateway::factory(),
            'transaction_id' => $this->faker->unique()->sha256(),
            'amount' => $this->faker->numberBetween(100000, 500000),
            'currency' => 'IDR',
            'status' => PaymentStatus::Pending,
            'transaction_type' => TransactionType::TierPurchase,
        ];
    }
}
