<?php

namespace App\Models;

use App\Enums\ProctorSnapshotType;
use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\ProctorSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $captured_at_display
 */
#[Fillable(['proctor_session_id', 'type', 'captured_at', 'file_url', 'triggered_by_event_id'])]
class ProctorSnapshot extends Model
{
    /** @use HasFactory<ProctorSnapshotFactory> */
    use HasFactory, HasUuid, HasViewerTimezoneDates;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ProctorSnapshotType::class,
        'captured_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ProctorSession, $this>
     */
    public function proctorSession(): BelongsTo
    {
        return $this->belongsTo(ProctorSession::class);
    }

    /**
     * @return BelongsTo<ProctorEvent, $this>
     */
    public function triggeredByEvent(): BelongsTo
    {
        return $this->belongsTo(ProctorEvent::class, 'triggered_by_event_id');
    }
}
