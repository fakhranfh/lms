<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaLibraryItem>
 */
class MediaLibraryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(MaterialType::cases());

        return [
            'id' => (string) Str::uuid(),
            'school_id' => School::factory(),
            'uploaded_by' => User::factory(),
            'type' => $type,
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'file_path' => 'media/'.$this->faker->uuid().'.'.$this->faker->fileExtension(),
            'file_size' => $this->faker->numberBetween(1024, $type->maxSize()),
            'mime_type' => $this->faker->mimeType(),
        ];
    }

    public function withSchool(School $school): self
    {
        return $this->state([
            'school_id' => $school->id,
        ]);
    }

    public function withType(MaterialType $type): self
    {
        return $this->state([
            'type' => $type,
        ]);
    }
}
