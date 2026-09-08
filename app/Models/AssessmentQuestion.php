<?php

namespace App\Models;

use App\Enums\AssessmentQuestionType;
use App\Traits\HasUuid;
use Database\Factories\AssessmentQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assessment_id', 'description', 'points', 'question_type', 'order'])]
class AssessmentQuestion extends Model
{
    /** @use HasFactory<AssessmentQuestionFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'question_type' => AssessmentQuestionType::class,
    ];

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return HasMany<AssessmentQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(AssessmentQuestionOption::class)->orderBy('order');
    }

    /**
     * @return BelongsToMany<MediaLibraryItem, $this>
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(MediaLibraryItem::class, 'assessment_question_files', 'assessment_question_id', 'media_library_item_id')
            ->withPivot('order')
            ->withTimestamps();
    }
}
