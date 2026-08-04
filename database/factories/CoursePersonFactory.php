<?php

namespace Database\Factories;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CoursePerson>
 */
class CoursePersonFactory extends Factory
{
    protected $model = CoursePerson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'role_in_course' => RoleInCourse::Student,
            'enrolled_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => CourseMembershipStatus::Active,
        ];
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['role_in_course' => RoleInCourse::Teacher]);
    }

    public function student(): static
    {
        return $this->state(fn () => ['role_in_course' => RoleInCourse::Student]);
    }
}
