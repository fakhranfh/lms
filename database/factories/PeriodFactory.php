<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    protected $model = Period::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'title' => fake()->randomElement(['Midterm Period', 'Final Period']),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
