<?php

namespace Database\Factories;

use App\Models\PaymentGatewayCredential;
use App\Models\SchoolPaymentGateway;
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
            'school_payment_gateway_id' => SchoolPaymentGateway::factory(),
            'credential_key' => $this->faker->word(),
            'credential_value' => $this->faker->sha256(),
            'is_sensitive' => true,
        ];
    }
}
