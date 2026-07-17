<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonMaterial>
 */
class LessonMaterialFactory extends Factory
{
    private static int $orderCounter = 0;

    private static ?string $lastLessonId = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(MaterialType::cases());

        return [
            'lesson_id' => Lesson::factory(),
            'type' => $type,
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'file_url' => $this->faker->url(),
            'file_path' => 'materials/'.$this->faker->uuid().'.'.$this->faker->fileExtension(),
            'file_size' => $this->faker->numberBetween(1024, $type->maxSize()),
            'mime_type' => $this->faker->mimeType(),
            'order' => function ($attributes) {
                $lessonId = $attributes['lesson_id'];

                if ($lessonId !== self::$lastLessonId) {
                    self::$lastLessonId = $lessonId;
                    self::$orderCounter = 0;
                }

                self::$orderCounter++;

                return self::$orderCounter;
            },
        ];
    }

    public function withLesson(Lesson $lesson): self
    {
        return $this->state([
            'lesson_id' => $lesson->id,
        ]);
    }

    public function withType(MaterialType $type): self
    {
        return $this->state([
            'type' => $type,
        ]);
    }

    public function withOrder(int $order): self
    {
        return $this->state([
            'order' => $order,
        ]);
    }
}
