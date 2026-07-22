<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $module = Module::factory();

        return [
            'id' => (string) Str::uuid(),
            'module_id' => $module,
            'title' => fake()->sentence(2),
            'content' => fake()->paragraphs(3, true),
            'order' => function ($attributes) {
                $maxOrder = Lesson::where('module_id', $attributes['module_id'])->max('order') ?? 0;

                return $maxOrder + 1;
            },
            'is_published' => fake()->boolean(30),
            'duration_minutes' => fake()->numberBetween(5, 60),
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
