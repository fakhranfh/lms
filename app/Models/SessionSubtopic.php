<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SessionSubtopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'subtopic', 'order'])]
class SessionSubtopic extends Model
{
    /** @use HasFactory<SessionSubtopicFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
