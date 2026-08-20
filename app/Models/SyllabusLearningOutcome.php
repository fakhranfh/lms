<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusLearningOutcomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['syllabus_id', 'code', 'description', 'order'])]
class SyllabusLearningOutcome extends Model
{
    /** @use HasFactory<SyllabusLearningOutcomeFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Syllabus, $this>
     */
    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    /**
     * @return HasMany<SyllabusRubricKeyIndicator, $this>
     */
    public function rubricKeyIndicators(): HasMany
    {
        return $this->hasMany(SyllabusRubricKeyIndicator::class, 'learning_outcome_id')->orderBy('order');
    }

    /**
     * @return BelongsToMany<SyllabusEvaluationActivity, $this>
     */
    public function evaluationActivities(): BelongsToMany
    {
        return $this->belongsToMany(
            SyllabusEvaluationActivity::class,
            'syllabus_evaluation_activity_learning_outcome',
            'learning_outcome_id',
            'syllabus_evaluation_activity_id',
        )->withTimestamps();
    }
}
