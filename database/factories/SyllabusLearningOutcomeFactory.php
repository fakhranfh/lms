<?php

namespace Database\Factories;

use App\Models\Syllabus;
use App\Models\SyllabusLearningOutcome;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusLearningOutcome>
 */
class SyllabusLearningOutcomeFactory extends Factory
{
    protected $model = SyllabusLearningOutcome::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $n = 0;
        $n++;

        return [
            'id' => (string) Str::uuid(),
            'syllabus_id' => Syllabus::factory(),
            'code' => 'LO'.$n,
            'description' => fake()->paragraph(),
            'order' => $n,
        ];
    }
}
