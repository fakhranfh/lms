<?php

namespace Database\Factories;

use App\Enums\ProctorEventType;
use App\Enums\ProctorSeverity;
use App\Models\ProctorEvent;
use App\Models\ProctorSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProctorEvent>
 */
class ProctorEventFactory extends Factory
{
    protected $model = ProctorEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'proctor_session_id' => ProctorSession::factory(),
            'event_type' => fake()->randomElement(ProctorEventType::cases()),
            'severity' => fake()->randomElement(ProctorSeverity::cases()),
            'detected_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'metadata' => null,
        ];
    }
}
