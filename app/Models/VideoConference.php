<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\VideoConferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['session_id', 'title', 'scheduled_start_at', 'scheduled_end_at', 'meeting_url', 'required_duration_minutes'])]
class VideoConference extends Model
{
    /** @use HasFactory<VideoConferenceFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * @return HasMany<VideoConferenceParticipation, $this>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(VideoConferenceParticipation::class);
    }
}
