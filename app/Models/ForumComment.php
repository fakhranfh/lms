<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ForumCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['thread_id', 'user_id', 'body'])]
class ForumComment extends Model
{
    /** @use HasFactory<ForumCommentFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<ForumThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ForumCommentLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(ForumCommentLike::class, 'comment_id');
    }
}
