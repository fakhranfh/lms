<?php

namespace App\Models;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Traits\HasUuid;
use Database\Factories\CoursePersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'user_id', 'role_in_course', 'enrolled_at', 'status'])]
class CoursePerson extends Model
{
    /** @use HasFactory<CoursePersonFactory> */
    use HasFactory, HasUuid;

    protected $table = 'course_people';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'role_in_course' => RoleInCourse::class,
        'status' => CourseMembershipStatus::class,
        'enrolled_at' => 'datetime',
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
}
