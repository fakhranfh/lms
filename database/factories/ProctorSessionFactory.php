<?php

namespace Database\Factories;

use App\Enums\ProctorSessionStatus;
use App\Models\AssessmentAttempt;
use App\Models\ProctorSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProctorSession>
 */
class ProctorSessionFactory extends Factory
{
    protected $model = ProctorSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_attempt_id' => AssessmentAttempt::factory(),
            'status' => ProctorSessionStatus::Active,
            'started_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'ended_at' => null,
            'risk_score' => fake()->numberBetween(0, 100),
        ];
    }
}
