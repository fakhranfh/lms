<?php

namespace Database\Factories;

use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'title' => 'Session '.fake()->numberBetween(1, 14).': '.fake()->sentence(3),
            'learning_outcome' => fake()->paragraph(),
            'date_start' => $start,
            'date_end' => (clone $start)->modify('+7 days'),
            'delivery_mode' => fake()->randomElement(DeliveryMode::cases()),
            'order' => fake()->unique()->numberBetween(1, 1000),
        ];
    }
}
