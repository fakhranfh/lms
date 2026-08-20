<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ForumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'session_id', 'title', 'created_by'])]
class Forum extends Model
{
    /** @use HasFactory<ForumFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ForumThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }
}
