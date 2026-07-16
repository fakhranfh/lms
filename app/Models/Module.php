<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'order', 'is_published'])]
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
            ->orderByDesc('order')
            ->first();

        if ($previousModule) {
            $tempOrder = 0;
            $this->update(['order' => $tempOrder]);
            $previousModule->update(['order' => $this->order]);
            $this->update(['order' => $previousModule->order]);
        }
    }

    public function moveDown(): void
    {
        $nextModule = $this->course
            ->modules()
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();

        if ($nextModule) {
            $tempOrder = 999999;
            $this->update(['order' => $tempOrder]);
            $nextModule->update(['order' => $this->order]);
            $this->update(['order' => $nextModule->order]);
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
