<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'user_id' => User::factory(),
            'event' => 'updated',
            'auditable_type' => Course::class,
            'auditable_id' => (string) Str::uuid(),
            'old_values' => ['title' => $this->faker->sentence(2)],
            'new_values' => ['title' => $this->faker->sentence(2)],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'description' => 'Course updated',
        ];
    }
}
