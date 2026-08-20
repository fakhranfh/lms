<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusRubricProficiencyLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['syllabus_id', 'label', 'score_min', 'score_max', 'order'])]
class SyllabusRubricProficiencyLevel extends Model
{
    /** @use HasFactory<SyllabusRubricProficiencyLevelFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Syllabus, $this>
     */
    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    /**
     * @return HasMany<SyllabusRubricCell, $this>
     */
    public function cells(): HasMany
    {
        return $this->hasMany(SyllabusRubricCell::class, 'rubric_proficiency_level_id');
    }
}
