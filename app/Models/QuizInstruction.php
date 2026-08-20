<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\QuizInstructionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content', 'updated_by'])]
class QuizInstruction extends Model
{
    /** @use HasFactory<QuizInstructionFactory> */
    use HasFactory, HasUuid;

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
