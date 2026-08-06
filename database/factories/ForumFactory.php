<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Forum;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    protected $model = Forum::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'course_id' => Course::factory(),
            'session_id' => Session::factory(),
            'title' => fake()->sentence(3),
        ];
    }
}
