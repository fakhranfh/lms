<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Traits\HasUuid;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'learning_outcome', 'date_start', 'date_end', 'delivery_mode'])]
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
     * @return BelongsToMany<LessonMaterial, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(LessonMaterial::class, 'session_materials')
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
}
