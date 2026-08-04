<?php

namespace Database\Factories;

use App\Models\ForumComment;
use App\Models\ForumCommentLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumCommentLike>
 */
class ForumCommentLikeFactory extends Factory
{
    protected $model = ForumCommentLike::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'comment_id' => ForumComment::factory(),
            'user_id' => User::factory(),
        ];
    }
}
