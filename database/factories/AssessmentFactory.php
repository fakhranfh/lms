<?php

namespace Database\Factories;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AssessmentType::cases());

        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'session_id' => null,
            'type' => $type,
            'title' => fake()->sentence(3),
            'weight' => $type->defaultWeight(),
            'assigned_to' => AssessmentAssignedTo::Individual,
            'start_date' => fake()->dateTimeBetween('now', '+1 week'),
            'end_date' => fake()->dateTimeBetween('+2 weeks', '+1 month'),
            'status' => AssessmentStatus::Published,
        ];
    }
}
