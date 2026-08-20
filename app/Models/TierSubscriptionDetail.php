<?php

namespace App\Models;

use App\Enums\TierChangeType;
use App\Traits\HasUuid;
use Database\Factories\TierSubscriptionDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_transaction_id', 'school_id', 'subscription_id', 'tier_name', 'billing_period', 'from_tier_id', 'change_type', 'proration_amount', 'registration_data'])]
class TierSubscriptionDetail extends Model
{
    /** @use HasFactory<TierSubscriptionDetailFactory> */
    use HasFactory, HasUuid;

    protected $table = 'payment_transaction_tier_subscriptions';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'change_type' => TierChangeType::class,
        'proration_amount' => 'decimal:2',
        'registration_data' => 'array',
    ];

    /**
     * Get the parent payment transaction.
     *
     * @return BelongsTo<PaymentTransaction, $this>
     */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
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
     * Get the school tier subscription.
     *
     * @return BelongsTo<SchoolTier, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SchoolTier::class, 'subscription_id');
    }

    /**
     * Get the tier this subscription is changing from.
     *
     * @return BelongsTo<PricingTier, $this>
     */
    public function fromTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'from_tier_id');
    }
}
