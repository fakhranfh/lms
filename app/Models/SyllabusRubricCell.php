<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusRubricCellFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rubric_key_indicator_id', 'rubric_proficiency_level_id', 'description'])]
class SyllabusRubricCell extends Model
{
    /** @use HasFactory<SyllabusRubricCellFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<SyllabusRubricKeyIndicator, $this>
     */
    public function keyIndicator(): BelongsTo
    {
        return $this->belongsTo(SyllabusRubricKeyIndicator::class, 'rubric_key_indicator_id');
    }

    /**
     * @return BelongsTo<SyllabusRubricProficiencyLevel, $this>
     */
    public function proficiencyLevel(): BelongsTo
    {
        return $this->belongsTo(SyllabusRubricProficiencyLevel::class, 'rubric_proficiency_level_id');
    }
}
