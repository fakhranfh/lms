<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VideoConference;
use App\Models\VideoConferenceParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VideoConferenceParticipation>
 */
class VideoConferenceParticipationFactory extends Factory
{
    protected $model = VideoConferenceParticipation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joined = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'id' => (string) Str::uuid(),
            'video_conference_id' => VideoConference::factory(),
            'user_id' => User::factory(),
            'joined_at' => $joined,
            'left_at' => (clone $joined)->modify('+1 hour'),
        ];
    }
}
