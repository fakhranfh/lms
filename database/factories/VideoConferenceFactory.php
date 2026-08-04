<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\VideoConference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VideoConference>
 */
class VideoConferenceFactory extends Factory
{
    protected $model = VideoConference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'id' => (string) Str::uuid(),
            'session_id' => Session::factory(),
            'title' => 'Main Meeting',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => (clone $start)->modify('+2 hours'),
            'meeting_url' => fake()->url(),
            'required_duration_minutes' => 90,
        ];
    }
}
