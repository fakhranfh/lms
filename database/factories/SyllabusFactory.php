<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Syllabus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Syllabus>
 */
class SyllabusFactory extends Factory
{
    protected $model = Syllabus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'course_description' => fake()->paragraph(),
            'submission_and_collection' => fake()->paragraph(),
            'tutorial_activity_plan' => fake()->paragraph(),
            'teaching_learning_strategies' => fake()->paragraph(),
            'textbooks' => fake()->sentence(),
            'competency_map' => fake()->paragraph(),
            'video_overview' => fake()->sentence(),
        ];
    }
}
