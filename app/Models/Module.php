<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'description', 'order', 'is_published'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory, HasUuid;

    /**
     * Get the course that owns this module.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons for this module.
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function moveUp(): void
    {
        $previousModule = $this->course
            ->modules()
            ->where('order', '<', $this->order)
            ->reorder('order', 'desc')
            ->first();

        if ($previousModule) {
            $thisOrder = $this->order;
            $previousOrder = $previousModule->order;

            $this->update(['order' => 0]);
            $previousModule->update(['order' => $thisOrder]);
            $this->update(['order' => $previousOrder]);
        }
    }

    public function moveDown(): void
    {
        $nextModule = $this->course
            ->modules()
            ->where('order', '>', $this->order)
            ->reorder('order', 'asc')
            ->first();

        if ($nextModule) {
            $thisOrder = $this->order;
            $nextOrder = $nextModule->order;

            $this->update(['order' => 999999]);
            $nextModule->update(['order' => $thisOrder]);
            $this->update(['order' => $nextOrder]);
        }
    }

    public function lessonsCount(): int
    {
        return $this->lessons()->count();
    }

    public function nextOrder(): int
    {
        return ($this->lessons()->max('order') ?? 0) + 1;
    }
}
