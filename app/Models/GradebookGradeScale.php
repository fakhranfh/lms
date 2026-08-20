<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\GradebookGradeScaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'label', 'score_min', 'score_max', 'order'])]
class GradebookGradeScale extends Model
{
    /** @use HasFactory<GradebookGradeScaleFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
