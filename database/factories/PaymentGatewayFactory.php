<?php

namespace Database\Factories;

use App\Models\PaymentGatewayType;
use App\Models\School;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
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
            'gateway_type_id' => PaymentGatewayType::factory(),
            'is_enabled' => false,
            'is_sandbox_mode' => true,
            'webhook_secret' => $this->faker->sha256(),
        ];
    }
}
