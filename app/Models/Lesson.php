<?php

namespace App\Models;

use App\Models\Concerns\TracksPublishedAt;
use App\Traits\HasUuid;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['module_id', 'title', 'content', 'order', 'duration_minutes', 'is_published'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, HasUuid, TracksPublishedAt;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the module that owns this lesson.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the users who have completed this lesson.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_user')
            ->withPivot('completed_at', 'last_viewed_at')
            ->withTimestamps();
    }

    /**
     * Get the materials for this lesson.
     *
     * @return HasMany<LessonMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(LessonMaterial::class);
    }

    /**
     * Get materials ordered by order column.
     *
     * @return Collection<int, LessonMaterial>
     */
    public function getMaterialsOrdered(): Collection
    {
        return $this->materials()->orderBy('order')->get();
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('completed_at', '!=', null)
            ->exists();
    }

    public function markCompleteFor(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['completed_at' => now()],
        ]);
    }

    public function moveUp(): void
    {
        $previousLesson = $this->module
            ->lessons()
            ->where('order', '<', $this->order)
            ->reorder('order', 'desc')
            ->first();

        if ($previousLesson) {
            $thisOrder = $this->order;
            $previousOrder = $previousLesson->order;

            $this->update(['order' => 0]);
            $previousLesson->update(['order' => $thisOrder]);
            $this->update(['order' => $previousOrder]);
        }
    }

    public function moveDown(): void
    {
        $nextLesson = $this->module
            ->lessons()
            ->where('order', '>', $this->order)
            ->reorder('order', 'asc')
            ->first();

        if ($nextLesson) {
            $thisOrder = $this->order;
            $nextOrder = $nextLesson->order;

            $this->update(['order' => 999999]);
            $nextLesson->update(['order' => $thisOrder]);
            $this->update(['order' => $nextOrder]);
        }
    }

    public function isPublished(): bool
    {
        return $this->is_published;
    }
}
