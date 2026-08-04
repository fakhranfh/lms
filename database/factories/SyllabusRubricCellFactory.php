<?php

namespace Database\Factories;

use App\Models\SyllabusRubricCell;
use App\Models\SyllabusRubricKeyIndicator;
use App\Models\SyllabusRubricProficiencyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusRubricCell>
 */
class SyllabusRubricCellFactory extends Factory
{
    protected $model = SyllabusRubricCell::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'rubric_key_indicator_id' => SyllabusRubricKeyIndicator::factory(),
            'rubric_proficiency_level_id' => SyllabusRubricProficiencyLevel::factory(),
            'description' => fake()->paragraph(),
        ];
    }
}
