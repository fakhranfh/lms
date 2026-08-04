<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusEvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['syllabus_id', 'class_type', 'order'])]
class SyllabusEvaluation extends Model
{
    /** @use HasFactory<SyllabusEvaluationFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Syllabus, $this>
     */
    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    /**
     * @return HasMany<SyllabusEvaluationActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(SyllabusEvaluationActivity::class)->orderBy('order');
    }
}
