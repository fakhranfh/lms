<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\SchoolPaymentGateway;
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
            'school_payment_gateway_id' => SchoolPaymentGateway::factory(),
            'transaction_id' => $this->faker->unique()->sha256(),
            'amount' => $this->faker->numberBetween(100000, 500000),
            'currency' => 'IDR',
            'status' => PaymentStatus::Pending,
            'metadata' => [],
        ];
    }
}
