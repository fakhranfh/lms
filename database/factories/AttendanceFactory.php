<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'session_id' => Session::factory(),
            'user_id' => User::factory(),
            'status' => AttendanceStatus::Present,
            'recorded_by' => User::factory(),
            'recorded_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'notes' => null,
        ];
    }
}
