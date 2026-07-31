<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'tier_id', 'status', 'started_at', 'expires_at', 'renewal_date', 'auto_renew', 'payment_method'])]
class Subscription extends Model
{
    use BelongsToSchool, HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
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
     * Get the subscription tier.
     *
     * @return BelongsTo<SubscriptionTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }

    /**
     * Get the payment transactions.
     *
     * @return HasMany<PaymentTransaction, $this>
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
