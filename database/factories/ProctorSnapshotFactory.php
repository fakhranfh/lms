<?php

namespace Database\Factories;

use App\Enums\ProctorSnapshotType;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProctorSnapshot>
 */
class ProctorSnapshotFactory extends Factory
{
    protected $model = ProctorSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'proctor_session_id' => ProctorSession::factory(),
            'type' => ProctorSnapshotType::Webcam,
            'captured_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'file_url' => fake()->imageUrl(),
            'triggered_by_event_id' => null,
        ];
    }
}
