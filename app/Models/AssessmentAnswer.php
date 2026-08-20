<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssessmentAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_attempt_id', 'answer_text', 'answer_file_id', 'comment', 'score'])]
class AssessmentAnswer extends Model
{
    /** @use HasFactory<AssessmentAnswerFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<AssessmentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    /**
     * @return BelongsTo<MediaLibraryItem, $this>
     */
    public function answerFile(): BelongsTo
    {
        return $this->belongsTo(MediaLibraryItem::class, 'answer_file_id');
    }
}
