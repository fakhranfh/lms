<?php

namespace Database\Factories;

use App\Models\Syllabus;
use App\Models\SyllabusRubricProficiencyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusRubricProficiencyLevel>
 */
class SyllabusRubricProficiencyLevelFactory extends Factory
{
    protected $model = SyllabusRubricProficiencyLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'syllabus_id' => Syllabus::factory(),
            'label' => fake()->randomElement(['Excellent', 'Good', 'Average', 'Poor']),
            'score_min' => 0,
            'score_max' => 100,
            'order' => 1,
        ];
    }
}
