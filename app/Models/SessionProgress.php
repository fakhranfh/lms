<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SessionProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'user_id', 'percent'])]
class SessionProgress extends Model
{
    /** @use HasFactory<SessionProgressFactory> */
    use HasFactory, HasUuid;

    protected $table = 'session_progress';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'percent' => 'integer',
    ];

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
