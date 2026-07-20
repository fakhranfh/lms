<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'id' => Str::uuid(),
            'school_id' => School::factory(),
            'title' => $title,
            'description' => fake()->paragraph(),
            'created_by' => User::factory(),
            'is_published' => fake()->boolean(30),
            'slug' => Str::slug($title),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
        ]);
    }
}
