<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\TierChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_tier_id', 'from_tier_id', 'to_tier_id', 'change_type', 'reason', 'changed_at'])]
class TierChange extends Model
{
    /** @use HasFactory<TierChangeFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Get the school tier.
     *
     * @return BelongsTo<SchoolTier, $this>
     */
    public function schoolTier(): BelongsTo
    {
        return $this->belongsTo(SchoolTier::class, 'school_tier_id');
    }

    /**
     * Get the from tier.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function fromTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'from_tier_id');
    }

    /**
     * Get the to tier.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function toTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'to_tier_id');
    }
}
