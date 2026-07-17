<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['module_id', 'title', 'content', 'video_embed_url', 'order', 'duration_minutes', 'is_published'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, HasUuid;

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

    public function isCompletedBy(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->where('completed_at', '!=', null)
            ->exists();
    }

    public function markCompleteFor(User $user): void
    {
        $existing = $this->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        if ($existing) {
            $this->users()
                ->updateExistingPivot($user->id, [
                    'completed_at' => now(),
                    'last_viewed_at' => now(),
                ]);
        } else {
            $this->users()->attach($user->id, [
                'completed_at' => now(),
                'last_viewed_at' => now(),
            ]);
        }
    }

    public function isPublished(): bool
    {
        return $this->is_published;
    }
}
