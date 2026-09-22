<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasUuid;
use Database\Factories\SchoolTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'tier_id', 'status', 'started_at', 'expires_at', 'renewal_date', 'auto_renew', 'payment_method'])]
class SchoolTier extends Model
{
    /** @use HasFactory<SchoolTierFactory> */
    use BelongsToSchool, HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => SubscriptionStatus::class,
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_date' => 'datetime',
        'auto_renew' => 'boolean',
    ];

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
     * Get the pricing tier.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class);
    }

    /**
     * Get the tier changes.
     *
     * @return HasMany<TierChange, $this>
     */
    public function tierChanges(): HasMany
    {
        return $this->hasMany(TierChange::class, 'school_tier_id');
    }
}
