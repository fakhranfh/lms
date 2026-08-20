<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['course_id', 'title', 'order'])]
class Period extends Model
{
    /** @use HasFactory<PeriodFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsToMany<Session, $this>
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Session::class, 'period_sessions')
            ->withPivot('order')
            ->withTimestamps();
    }
}
