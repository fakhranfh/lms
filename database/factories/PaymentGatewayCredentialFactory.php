<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGatewayCredential>
 */
class PaymentGatewayCredentialFactory extends Factory
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
            'credential_key' => $this->faker->word(),
            'credential_value' => $this->faker->sha256(),
            'is_sensitive' => true,
        ];
    }
}
