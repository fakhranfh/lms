<?php

namespace Database\Factories;

use App\Enums\FinalExamType;
use App\Models\Assessment;
use App\Models\FinalExam;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinalExam>
 */
class FinalExamFactory extends Factory
{
    protected $model = FinalExam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_id' => Assessment::factory(),
            'period_id' => Period::factory(),
            'exam_type' => FinalExamType::TakeHome,
            'start_date' => fake()->dateTimeBetween('now', '+1 week'),
            'end_date' => fake()->dateTimeBetween('+2 weeks', '+1 month'),
        ];
    }
}
