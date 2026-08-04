<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssessmentAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['assessment_id', 'user_id', 'group_id', 'submitted_by', 'attempt_number', 'started_at', 'submitted_at'])]
class AssessmentAttempt extends Model
{
    /** @use HasFactory<AssessmentAttemptFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return HasOne<AssessmentScore, $this>
     */
    public function score(): HasOne
    {
        return $this->hasOne(AssessmentScore::class);
    }

    /**
     * @return HasOne<AssessmentAnswer, $this>
     */
    public function answer(): HasOne
    {
        return $this->hasOne(AssessmentAnswer::class);
    }

    /**
     * @return HasMany<AssessmentQuizAnswer, $this>
     */
    public function quizAnswers(): HasMany
    {
        return $this->hasMany(AssessmentQuizAnswer::class);
    }

    /**
     * @return HasOne<ProctorSession, $this>
     */
    public function proctorSession(): HasOne
    {
        return $this->hasOne(ProctorSession::class);
    }
}
