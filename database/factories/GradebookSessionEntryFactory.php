<?php

namespace Database\Factories;

use App\Models\GradebookEntry;
use App\Models\GradebookSessionEntry;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GradebookSessionEntry>
 */
class GradebookSessionEntryFactory extends Factory
{
    protected $model = GradebookSessionEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'gradebook_entry_id' => GradebookEntry::factory(),
            'session_id' => Session::factory(),
            'weight' => 1,
            'score' => fake()->numberBetween(60, 100),
        ];
    }
}
