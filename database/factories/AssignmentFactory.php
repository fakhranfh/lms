<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => $this->faker->sentence(4),
            'prompt_question' => $this->faker->paragraph(),
            'rubric' => null,
            'max_score' => 100.00,
            'passing_score' => 60.00,
            'is_published' => false,
            'allow_multiple_submissions' => false,
        ];
    }
}
