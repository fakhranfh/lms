<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\CourseAttendanceSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'minimal_attendance'])]
class CourseAttendanceSetting extends Model
{
    /** @use HasFactory<CourseAttendanceSettingFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
