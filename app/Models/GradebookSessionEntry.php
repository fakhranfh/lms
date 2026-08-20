<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\GradebookSessionEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gradebook_entry_id', 'session_id', 'weight', 'score'])]
class GradebookSessionEntry extends Model
{
    /** @use HasFactory<GradebookSessionEntryFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<GradebookEntry, $this>
     */
    public function gradebookEntry(): BelongsTo
    {
        return $this->belongsTo(GradebookEntry::class);
    }

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
