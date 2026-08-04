<?php

namespace App\Models;

use App\Enums\AssessmentType;
use App\Traits\HasUuid;
use Database\Factories\GradebookEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'user_id', 'assessment_type', 'weight', 'score', 'last_updated_at'])]
class GradebookEntry extends Model
{
    /** @use HasFactory<GradebookEntryFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'assessment_type' => AssessmentType::class,
        'last_updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GradebookSessionEntry, $this>
     */
    public function sessionEntries(): HasMany
    {
        return $this->hasMany(GradebookSessionEntry::class);
    }
}
