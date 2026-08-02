<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Services\R2StorageService;
use App\Traits\HasUuid;
use Database\Factories\MediaLibraryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string|null $file_url
 */
#[Fillable(['school_id', 'uploaded_by', 'type', 'title', 'description', 'file_path', 'file_size', 'mime_type'])]
class MediaLibraryItem extends Model
{
    /** @use HasFactory<MediaLibraryItemFactory> */
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
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
