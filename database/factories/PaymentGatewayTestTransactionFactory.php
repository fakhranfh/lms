<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayTestTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGatewayTestTransaction>
 */
class PaymentGatewayTestTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_gateway_id' => PaymentGateway::factory(),
            'transaction_id' => 'test-'.$this->faker->uuid(),
            'status' => 'pending',
        ];
    }
}
