<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Traits\HasUuid;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $date_start_display
 * @property-read Carbon $date_end_display
 */
#[Fillable(['course_id', 'title', 'learning_outcome', 'date_start', 'date_end', 'delivery_mode', 'required_forum_posts', 'order'])]
class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory, HasUuid;

    protected $table = 'course_sessions';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'delivery_mode' => DeliveryMode::class,
        'required_forum_posts' => 'integer',
        'order' => 'integer',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<SessionSubtopic, $this>
     */
    public function subtopics(): HasMany
    {
        return $this->hasMany(SessionSubtopic::class)->orderBy('order');
    }

    /**
     * @return BelongsToMany<MediaLibraryItem, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(MediaLibraryItem::class, 'session_materials', 'session_id', 'media_library_item_id')
            ->withPivot('order')
            ->withTimestamps();
    }

    /**
     * @return HasMany<VideoConference, $this>
     */
    public function videoConferences(): HasMany
    {
        return $this->hasMany(VideoConference::class);
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * @return HasMany<Forum, $this>
     */
    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class);
    }

    /**
     * @return HasMany<SessionProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(SessionProgress::class, 'session_id');
    }

    public function isOngoing(): bool
    {
        return now()->between($this->date_start, $this->date_end);
    }
}
