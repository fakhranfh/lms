<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SyllabusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'course_description',
    'submission_and_collection',
    'tutorial_activity_plan',
    'teaching_learning_strategies',
    'textbooks',
    'competency_map',
    'video_overview',
])]
class Syllabus extends Model
{
    /** @use HasFactory<SyllabusFactory> */
    use HasFactory, HasUuid;

    protected $table = 'syllabuses';

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<SyllabusClassPolicy, $this>
     */
    public function classPolicies(): HasMany
    {
        return $this->hasMany(SyllabusClassPolicy::class)->orderBy('order');
    }

    /**
     * @return HasMany<SyllabusLearningOutcome, $this>
     */
    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(SyllabusLearningOutcome::class)->orderBy('order');
    }

    /**
     * @return HasMany<SyllabusEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(SyllabusEvaluation::class)->orderBy('order');
    }

    /**
     * @return HasMany<SyllabusRubricProficiencyLevel, $this>
     */
    public function rubricProficiencyLevels(): HasMany
    {
        return $this->hasMany(SyllabusRubricProficiencyLevel::class)->orderBy('order');
    }

    /**
     * @return BelongsToMany<LessonMaterial, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(LessonMaterial::class, 'syllabus_materials')
            ->withPivot(['section', 'order'])
            ->withTimestamps();
    }
}
