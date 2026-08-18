<?php

namespace App\Models;

use App\Enums\ProctorEventType;
use App\Enums\ProctorSeverity;
use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\ProctorEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $detected_at_display
 */
#[Fillable(['proctor_session_id', 'event_type', 'severity', 'detected_at', 'metadata'])]
class ProctorEvent extends Model
{
    /** @use HasFactory<ProctorEventFactory> */
    use HasFactory, HasUuid, HasViewerTimezoneDates;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'event_type' => ProctorEventType::class,
        'severity' => ProctorSeverity::class,
        'detected_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<ProctorSession, $this>
     */
    public function proctorSession(): BelongsTo
    {
        return $this->belongsTo(ProctorSession::class);
    }
}
