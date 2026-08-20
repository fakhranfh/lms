<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Services\DemoLmsAccessService;
use App\Traits\HasUuid;
use Database\Factories\DemoLmsAccessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'user_id', 'access_token', 'role', 'expires_at', 'accessed_at'])]
class DemoLmsAccess extends Model
{
    /** @use HasFactory<DemoLmsAccessFactory> */
    use BelongsToSchool, HasFactory, HasUuid;

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

    /**
     * Get the school.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the demo login URL for this access token.
     */
    public function getLoginUrl(string $scheme = 'http', ?int $port = null): string
    {
        $service = app(DemoLmsAccessService::class);

        return $service->buildDemoLoginUrl($this->school, $this->access_token, $scheme, $port);
    }
}
