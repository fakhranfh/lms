<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusRubricKeyIndicatorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['learning_outcome_id', 'code', 'description', 'order'])]
class SyllabusRubricKeyIndicator extends Model
{
    /** @use HasFactory<SyllabusRubricKeyIndicatorFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<SyllabusLearningOutcome, $this>
     */
    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(SyllabusLearningOutcome::class, 'learning_outcome_id');
    }

    /**
     * @return HasMany<SyllabusRubricCell, $this>
     */
    public function cells(): HasMany
    {
        return $this->hasMany(SyllabusRubricCell::class, 'rubric_key_indicator_id');
    }
}
