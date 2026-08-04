<?php

namespace Database\Factories;

use App\Models\SyllabusEvaluation;
use App\Models\SyllabusEvaluationActivity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusEvaluationActivity>
 */
class SyllabusEvaluationActivityFactory extends Factory
{
    protected $model = SyllabusEvaluationActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'syllabus_evaluation_id' => SyllabusEvaluation::factory(),
            'activity' => fake()->randomElement(['Forum Discussion', 'Attendance', 'THEORY: Quiz']),
            'weight' => fake()->randomElement([10, 15, 20, 30]),
            'order' => 1,
        ];
    }
}
