<?php

namespace App\Models;

use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'domain', 'tier_id'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUuid, HasViewerTimezoneDates;

    protected $table = 'schools';

    /**
     * Get the users belonging to the school.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the payment gateways.
     *
     * @return HasMany<SchoolPaymentGateway, $this>
     */
    public function paymentGateways(): HasMany
    {
        return $this->hasMany(SchoolPaymentGateway::class);
    }

    /**
     * Get the pricing tier for this school.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'tier_id');
    }

    /**
     * Get the school tier subscriptions.
     *
     * @return HasMany<SchoolTier, $this>
     */
    public function schoolTiers(): HasMany
    {
        return $this->hasMany(SchoolTier::class);
    }

    /**
     * Get the current tier limit value for a specific limit key.
     */
    public function getCurrentTierLimit(string $limitKey): ?int
    {
        return $this->tier?->limits()
            ->where('limit_key', $limitKey)
            ->value('limit_value');
    }

    /**
     * Get the most recent active school tier subscription.
     */
    public function getCurrentSchoolTier(): ?SchoolTier
    {
        return $this->schoolTiers()
            ->latest('started_at')
            ->first();
    }

    /**
     * Get the roles for this school.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
