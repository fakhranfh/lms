<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SessionMaterialCompletionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'media_library_item_id', 'user_id', 'completed_at'])]
class SessionMaterialCompletion extends Model
{
    /** @use HasFactory<SessionMaterialCompletionFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * @return BelongsTo<MediaLibraryItem, $this>
     */
    public function mediaLibraryItem(): BelongsTo
    {
        return $this->belongsTo(MediaLibraryItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
