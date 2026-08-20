<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssessmentQuestionScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_attempt_id', 'assessment_question_id', 'score'])]
class AssessmentQuestionScore extends Model
{
    /** @use HasFactory<AssessmentQuestionScoreFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<AssessmentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    /**
     * @return BelongsTo<AssessmentQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
}
