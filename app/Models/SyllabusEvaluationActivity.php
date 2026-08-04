<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusEvaluationActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['syllabus_evaluation_id', 'activity', 'weight', 'order'])]
class SyllabusEvaluationActivity extends Model
{
    /** @use HasFactory<SyllabusEvaluationActivityFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<SyllabusEvaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SyllabusEvaluation::class, 'syllabus_evaluation_id');
    }

    /**
     * @return BelongsToMany<SyllabusLearningOutcome, $this>
     */
    public function learningOutcomes(): BelongsToMany
    {
        return $this->belongsToMany(
            SyllabusLearningOutcome::class,
            'syllabus_evaluation_activity_learning_outcome',
            'syllabus_evaluation_activity_id',
            'learning_outcome_id',
        )->withTimestamps();
    }
}
