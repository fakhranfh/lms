<?php

namespace App\Models;

use App\Enums\AttendanceRequirementType;
use App\Traits\HasUuid;
use Database\Factories\AttendanceRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'requirement_type', 'label', 'order'])]
class AttendanceRequirement extends Model
{
    /** @use HasFactory<AttendanceRequirementFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'requirement_type' => AttendanceRequirementType::class,
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
