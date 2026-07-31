<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Services\R2StorageService;
use App\Traits\HasUuid;
use Database\Factories\LessonMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read string|null $file_url
 */
#[Fillable(['lesson_id', 'type', 'title', 'description', 'file_url', 'file_path', 'file_size', 'mime_type', 'order', 'version', 'is_active'])]
class LessonMaterial extends Model
{
    /** @use HasFactory<LessonMaterialFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => MaterialType::class,
        'is_active' => 'boolean',
    ];

    /**
     * Build the file URL from the stored R2 key rather than the persisted
     * `file_url` column, so links keep working if the R2 base/custom domain
     * changes later.
     */
    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => ($attributes['file_path'] ?? null)
                ? app(R2StorageService::class)->getPublicUrl($attributes['file_path'])
                : $value,
        );
    }

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

    /**
     * Scope to filter only active materials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all versions of this material (by lesson_id + title).
     *
     * @return Collection<int, self>
     */
    public function getAllVersions()
    {
        return static::where('lesson_id', $this->lesson_id)
            ->where('title', $this->title)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get the current active version of this material.
     */
    public function getActiveVersion(): ?self
    {
        return static::where('lesson_id', $this->lesson_id)
            ->where('title', $this->title)
            ->where('is_active', true)
            ->first();
    }
}
