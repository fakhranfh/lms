<?php

namespace Database\Factories;

use App\Models\AssessmentQuestionScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentQuestionScore>
 */
class AssessmentQuestionScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
