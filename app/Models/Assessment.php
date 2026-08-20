<?php

namespace App\Models;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Traits\HasUuid;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'course_id',
    'session_id',
    'type',
    'title',
    'weight',
    'assigned_to',
    'start_date',
    'end_date',
    'status',
    'required_posts_per_session',
    'attempt_limit',
])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => AssessmentType::class,
        'assigned_to' => AssessmentAssignedTo::class,
        'status' => AssessmentStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

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
     * @return HasMany<AssessmentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /**
     * @return HasMany<AssessmentQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('order');
    }

    /**
     * @return HasOne<Quiz, $this>
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /**
     * @return HasOne<FinalExam, $this>
     */
    public function finalExam(): HasOne
    {
        return $this->hasOne(FinalExam::class);
    }
}
