<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lesson_id', 'title', 'prompt_question', 'rubric', 'max_score', 'passing_score', 'is_published', 'allow_multiple_submissions'])]
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'rubric' => 'array',
        'max_score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'is_published' => 'boolean',
        'allow_multiple_submissions' => 'boolean',
    ];

    /**
     * Get the lesson that owns this assignment.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the submissions for this assignment.
     *
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function getScore(Submission $submission): ?float
    {
        return $submission->teacher_score !== null
            ? (float) $submission->teacher_score
            : ($submission->ai_score !== null ? (float) $submission->ai_score : null);
    }

    public function isPassing(Submission $submission): bool
    {
        if ($this->passing_score === null) {
            return false;
        }

        $score = $this->getScore($submission);

        return $score !== null && $score >= (float) $this->passing_score;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rubricItems(): array
    {
        return $this->rubric ?? [];
    }
}
