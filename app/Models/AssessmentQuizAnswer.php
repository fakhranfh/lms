<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssessmentQuizAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_attempt_id', 'quiz_question_id', 'selected_option_id', 'answer_text', 'score'])]
class AssessmentQuizAnswer extends Model
{
    /** @use HasFactory<AssessmentQuizAnswerFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<AssessmentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    /**
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    /**
     * @return BelongsTo<QuizQuestionOption, $this>
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuizQuestionOption::class, 'selected_option_id');
    }
}
