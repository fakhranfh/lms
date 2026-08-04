<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\AssessmentQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['assessment_id', 'description', 'points', 'order'])]
class AssessmentQuestion extends Model
{
    /** @use HasFactory<AssessmentQuestionFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsToMany<LessonMaterial, $this>
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(LessonMaterial::class, 'assessment_question_files')
            ->withPivot('order')
            ->withTimestamps();
    }
}
