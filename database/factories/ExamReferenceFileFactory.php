<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Models\Assessment;
use App\Models\ExamReferenceFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamReferenceFile>
 */
class ExamReferenceFileFactory extends Factory
{
    protected $model = ExamReferenceFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_id' => Assessment::factory(),
            'user_id' => User::factory(),
            'type' => MaterialType::PDF,
            'title' => fake()->words(3, true),
            'file_path' => 'exam-reference/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1024, 1024 * 1024),
        ];
    }
}
