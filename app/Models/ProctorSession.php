<?php

namespace App\Models;

use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Traits\HasUuid;
use Database\Factories\ProctorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'assessment_attempt_id',
    'status',
    'started_at',
    'ended_at',
    'risk_score',
    'reviewed_by',
    'reviewed_at',
    'review_decision',
    'review_notes',
])]
class ProctorSession extends Model
{
    /** @use HasFactory<ProctorSessionFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ProctorSessionStatus::class,
        'review_decision' => ProctorReviewDecision::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<AssessmentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<ProctorEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ProctorEvent::class);
    }

    /**
     * @return HasMany<ProctorSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(ProctorSnapshot::class);
    }
}
