<?php

namespace Database\Factories;

use App\Enums\SyllabusPolicyScope;
use App\Models\Syllabus;
use App\Models\SyllabusClassPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyllabusClassPolicy>
 */
class SyllabusClassPolicyFactory extends Factory
{
    protected $model = SyllabusClassPolicy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'syllabus_id' => Syllabus::factory(),
            'scope' => fake()->randomElement(SyllabusPolicyScope::cases()),
            'content' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
