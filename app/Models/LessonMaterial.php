<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Traits\HasUuid;
use Database\Factories\LessonMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['lesson_id', 'type', 'title', 'description', 'file_url', 'file_path', 'file_size', 'mime_type', 'order'])]
class LessonMaterial extends Model
{
    /** @use HasFactory<LessonMaterialFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => MaterialType::class,
    ];

    /**
     * Get the lesson that owns this material.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the users who have accessed this material.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_material_user')
            ->withPivot('accessed_at')
            ->withTimestamps();
    }
}
