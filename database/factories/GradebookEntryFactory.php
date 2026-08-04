<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Course;
use App\Models\GradebookEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GradebookEntry>
 */
class GradebookEntryFactory extends Factory
{
    protected $model = GradebookEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'assessment_type' => fake()->randomElement(AssessmentType::cases()),
            'weight' => 10,
            'score' => fake()->numberBetween(60, 100),
            'last_updated_at' => now(),
        ];
    }
}
