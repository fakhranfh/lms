<?php

namespace Database\Factories;

use App\Models\PaymentWebhook;
use App\Models\SchoolPaymentGateway;
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
            'school_payment_gateway_id' => SchoolPaymentGateway::factory(),
            'event_type' => $this->faker->word(),
            'payload' => json_encode(['test' => 'data']),
            'processed' => false,
            'processed_at' => null,
        ];
    }
}
