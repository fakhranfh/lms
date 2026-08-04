<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\VideoConferenceParticipationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['video_conference_id', 'user_id', 'joined_at', 'left_at'])]
class VideoConferenceParticipation extends Model
{
    /** @use HasFactory<VideoConferenceParticipationFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<VideoConference, $this>
     */
    public function videoConference(): BelongsTo
    {
        return $this->belongsTo(VideoConference::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
