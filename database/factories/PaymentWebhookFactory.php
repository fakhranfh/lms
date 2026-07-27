<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Models\PaymentWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentWebhook>
 */
class PaymentWebhookFactory extends Factory
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
            'event_type' => $this->faker->word(),
            'payload' => json_encode(['test' => 'data']),
            'processed' => false,
            'processed_at' => null,
        ];
    }
}
