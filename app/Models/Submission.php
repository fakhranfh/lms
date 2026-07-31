<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Traits\HasUuid;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assignment_id', 'user_id', 'student_answer', 'status', 'ai_score', 'ai_feedback', 'teacher_score', 'teacher_feedback', 'reviewed_by', 'teacher_reviewed_at', 'submitted_at', 'graded_at', 'retry_count', 'error_message'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ai_feedback' => 'array',
        'status' => SubmissionStatus::class,
        'ai_score' => 'decimal:2',
        'teacher_score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'teacher_reviewed_at' => 'datetime',
    ];

    /**
     * Get the assignment that owns this submission.
     *
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the student who made this submission.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the teacher who reviewed this submission.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === SubmissionStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === SubmissionStatus::Processing;
    }

    public function isGraded(): bool
    {
        return $this->status === SubmissionStatus::Graded;
    }

    public function isFailed(): bool
    {
        return $this->status === SubmissionStatus::Failed;
    }

    public function overrideScore(float $score, string $feedback, User $teacher): void
    {
        $this->update([
            'teacher_score' => $score,
            'teacher_feedback' => $feedback,
            'reviewed_by' => $teacher->id,
            'teacher_reviewed_at' => now(),
        ]);
    }

    public function getDisplayScore(): ?float
    {
        return $this->teacher_score !== null
            ? (float) $this->teacher_score
            : ($this->ai_score !== null ? (float) $this->ai_score : null);
    }

    public function getDisplayFeedback(): ?string
    {
        if ($this->teacher_feedback !== null) {
            return $this->teacher_feedback;
        }

        if ($this->ai_feedback !== null) {
            return $this->ai_feedback['overall_feedback'] ?? $this->ai_feedback['feedback'] ?? json_encode($this->ai_feedback);
        }

        return null;
    }
}
