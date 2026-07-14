<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\DemoLmsAccessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'user_id', 'access_token', 'expires_at', 'accessed_at'])]
class DemoLmsAccess extends Model
{
    /** @use HasFactory<DemoLmsAccessFactory> */
    use BelongsToTenant, HasFactory, HasUuid;

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'accessed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
