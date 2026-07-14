<?php

namespace App\Models;

use Database\Factories\TierFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pricing_tier_id', 'feature_key', 'is_enabled'])]
class TierFeature extends Model
{
    /** @use HasFactory<TierFeatureFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

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
