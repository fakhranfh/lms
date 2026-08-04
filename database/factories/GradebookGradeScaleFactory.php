<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\GradebookGradeScale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GradebookGradeScale>
 */
class GradebookGradeScaleFactory extends Factory
{
    protected $model = GradebookGradeScale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'label' => 'A',
            'score_min' => 85,
            'score_max' => 100,
            'order' => 1,
        ];
    }
}
