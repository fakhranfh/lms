<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseAttendanceSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourseAttendanceSetting>
 */
class CourseAttendanceSettingFactory extends Factory
{
    protected $model = CourseAttendanceSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'minimal_attendance' => 12,
        ];
    }
}
