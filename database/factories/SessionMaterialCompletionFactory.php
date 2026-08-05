<?php

namespace Database\Factories;

use App\Models\MediaLibraryItem;
use App\Models\Session;
use App\Models\SessionMaterialCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SessionMaterialCompletion>
 */
class SessionMaterialCompletionFactory extends Factory
{
    protected $model = SessionMaterialCompletion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'session_id' => Session::factory(),
            'media_library_item_id' => MediaLibraryItem::factory(),
            'user_id' => User::factory(),
            'completed_at' => now(),
        ];
    }
}
