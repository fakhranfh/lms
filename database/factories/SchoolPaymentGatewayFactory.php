<?php

namespace Database\Factories;

use App\Models\PaymentGatewayType;
use App\Models\SchoolPaymentGateway;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolPaymentGateway>
 */
class SchoolPaymentGatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'gateway_type_id' => PaymentGatewayType::factory(),
            'is_enabled' => false,
            'is_sandbox_mode' => true,
            'webhook_secret' => $this->faker->sha256(),
        ];
    }
}
