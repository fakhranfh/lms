<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'module_id' => $module,
            'title' => fake()->sentence(2),
            'content' => fake()->paragraphs(3, true),
            'video_embed_url' => fake()->boolean(40) ? $this->generateVideoUrl() : null,
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

    public function withVideo(): static
    {
        return $this->state(fn () => [
            'video_embed_url' => $this->generateVideoUrl(),
        ]);
    }

    private function generateVideoUrl(): string
    {
        $videos = [
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/9bZkp7q19f0',
            'https://vimeo.com/12345678',
        ];

        return fake()->randomElement($videos);
    }
}
