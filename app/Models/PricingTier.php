<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use Database\Factories\PricingTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'price', 'currency', 'billing_period', 'is_active'])]
class PricingTier extends Model
{
    /** @use HasFactory<PricingTierFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'billing_period' => BillingPeriod::class,
        'is_active' => 'boolean',
    ];

    /**
     * Get the tier features.
     *
     * @return HasMany<TierFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(TierFeature::class, 'pricing_tier_id');
    }

    /**
     * Get the tier limits.
     *
     * @return HasMany<TierLimit, $this>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(TierLimit::class, 'pricing_tier_id');
    }

    /**
     * Get the school tiers using this pricing tier.
     *
     * @return HasMany<SchoolTier, $this>
     */
    public function schoolTiers(): HasMany
    {
        return $this->hasMany(SchoolTier::class, 'tier_id');
    }
}
