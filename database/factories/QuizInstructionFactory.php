<?php

namespace Database\Factories;

use App\Models\QuizInstruction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuizInstruction>
 */
class QuizInstructionFactory extends Factory
{
    protected $model = QuizInstruction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'content' => fake()->paragraph(),
            'updated_by' => User::factory(),
        ];
    }
}
