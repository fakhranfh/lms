<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ForumCommentLikeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['comment_id', 'user_id'])]
class ForumCommentLike extends Model
{
    /** @use HasFactory<ForumCommentLikeFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<ForumComment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'comment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
