<?php

namespace App\Models;

use App\Enums\QuizScoringMethod;
use App\Traits\HasUuid;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// NOTE: Quiz questions live on the shared assessment_questions table (see
// questions() below), scoped through the quiz's assessment_id rather than a
// dedicated quiz_id column.

#[Fillable(['assessment_id', 'start_date', 'due_date', 'total_question', 'total_attempts', 'scoring_method', 'time_limit_per_attempt'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'scoring_method' => QuizScoringMethod::class,
    ];

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return HasMany<AssessmentQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id', 'assessment_id')->orderBy('order');
    }
}
