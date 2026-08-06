<?php

namespace Database\Factories;

use App\Models\ForumThread;
use App\Models\ForumThreadRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumThreadRead>
 */
class ForumThreadReadFactory extends Factory
{
    protected $model = ForumThreadRead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'thread_id' => ForumThread::factory(),
            'user_id' => User::factory(),
            'read_at' => now(),
        ];
    }
}
