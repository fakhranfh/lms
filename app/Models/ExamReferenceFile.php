<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Services\R2StorageService;
use App\Traits\HasUuid;
use Database\Factories\ExamReferenceFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string|null $file_url
 */
#[Fillable(['assessment_id', 'user_id', 'type', 'title', 'file_path', 'file_size'])]
class ExamReferenceFile extends Model
{
    /** @use HasFactory<ExamReferenceFileFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => MaterialType::class,
    ];

    /**
     * Build the file URL from the stored R2 key on demand, rather than
     * persisting it, so links keep working if the R2 base/custom domain
     * changes later.
     */
    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => ($attributes['file_path'] ?? null)
                ? app(R2StorageService::class)->getPublicUrl($attributes['file_path'])
                : null,
        );
    }

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
