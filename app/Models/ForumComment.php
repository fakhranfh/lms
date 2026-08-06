<?php

namespace App\Models;

use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\ForumCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $created_at_display
 * @property-read Carbon $updated_at_display
 */
#[Fillable(['thread_id', 'parent_id', 'user_id', 'body'])]
class ForumComment extends Model
{
    /** @use HasFactory<ForumCommentFactory> */
    use HasFactory, HasUuid, HasViewerTimezoneDates;

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

    /**
     * @return BelongsTo<ForumComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'parent_id');
    }

    /**
     * @return HasMany<ForumComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'parent_id')->latest();
    }
}
