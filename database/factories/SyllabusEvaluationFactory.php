<?php

namespace Database\Factories;

use App\Models\Syllabus;
use App\Models\SyllabusEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusEvaluation>
 */
class SyllabusEvaluationFactory extends Factory
{
    protected $model = SyllabusEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'syllabus_id' => Syllabus::factory(),
            'class_type' => fake()->randomElement(['LEC', 'LAB']),
            'order' => 1,
        ];
    }
}
