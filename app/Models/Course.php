<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasUuid;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'created_by', 'title', 'description', 'slug', 'is_published'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use BelongsToSchool, HasFactory, HasUuid;

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
     * Get the modules for this course.
     *
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
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

    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }

    public function isPublished(): bool
    {
        return $this->is_published;
    }

    public function modulesCount(): int
    {
        return $this->modules()->count();
    }
}
