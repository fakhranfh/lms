<?php

namespace Database\Factories;

use App\Models\SyllabusLearningOutcome;
use App\Models\SyllabusRubricKeyIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusRubricKeyIndicator>
 */
class SyllabusRubricKeyIndicatorFactory extends Factory
{
    protected $model = SyllabusRubricKeyIndicator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'learning_outcome_id' => SyllabusLearningOutcome::factory(),
            'code' => fake()->numerify('#.#'),
            'description' => fake()->sentence(),
            'order' => 1,
        ];
    }
}
