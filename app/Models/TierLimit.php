<?php

namespace App\Models;

use Database\Factories\TierLimitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pricing_tier_id', 'limit_key', 'limit_value'])]
class TierLimit extends Model
{
    /** @use HasFactory<TierLimitFactory> */
    use HasFactory;

    /**
     * Get the pricing tier.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }
}
