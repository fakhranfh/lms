<?php

namespace Database\Factories;

use App\Enums\AttendanceRequirementType;
use App\Models\AttendanceRequirement;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttendanceRequirement>
 */
class AttendanceRequirementFactory extends Factory
{
    protected $model = AttendanceRequirement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'requirement_type' => AttendanceRequirementType::ForumCompleted,
            'label' => 'Forum Completed',
            'order' => 1,
        ];
    }
}
