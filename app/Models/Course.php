<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasUuid;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['school_id', 'created_by', 'title', 'description', 'grade_band_a_min', 'grade_band_b_min', 'grade_band_c_min', 'grade_band_d_min'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use BelongsToSchool, HasFactory, HasUuid, SoftDeletes;

    /**
     * Get the school that owns this course.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created this course.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Session, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * @return HasMany<Period, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(Period::class)->orderBy('order');
    }

    /**
     * @return HasOne<Syllabus, $this>
     */
    public function syllabus(): HasOne
    {
        return $this->hasOne(Syllabus::class);
    }

    /**
     * @return HasMany<CoursePerson, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(CoursePerson::class);
    }

    /**
     * @return HasMany<Group, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * @return HasMany<Forum, $this>
     */
    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class);
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
