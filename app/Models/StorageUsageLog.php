<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'total_used_bytes', 'quota_bytes', 'usage_percent', 'last_alert_threshold'])]
class StorageUsageLog extends Model
{
    protected $casts = [
        'usage_percent' => 'float',
    ];

    /**
     * Get the school this log entry belongs to (null for global entries).
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
