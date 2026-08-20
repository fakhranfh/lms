<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ForumThreadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $created_at_display
 * @property-read Carbon $updated_at_display
 */
#[Fillable(['forum_id', 'user_id', 'title', 'description'])]
class ForumThread extends Model
{
    /** @use HasFactory<ForumThreadFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Forum, $this>
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ForumComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'thread_id');
    }
}
