<?php

namespace App\Models;

use App\Enums\AdminFeeType;
use App\Enums\PaymentStatus;
use App\Enums\TierChangeType;
use App\Enums\TransactionType;
use App\Traits\HasUuid;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['initiated_by', 'payment_gateway_id', 'transaction_id', 'amount', 'currency', 'status', 'transaction_type', 'subtotal', 'vat_rate', 'vat_amount', 'admin_fee_rate', 'admin_fee_type', 'admin_fee_amount'])]
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory, HasUuid;

    /**
     * Keys that no longer live on this table and are instead stored on the
     * related TierSubscriptionDetail record.
     */
    private const DETAIL_KEYS = ['school_id', 'subscription_id', 'tier_name', 'billing_period', 'from_tier_id', 'change_type', 'proration_amount', 'registration_data'];

    /**
     * @var array<string, mixed>
     */
    private array $pendingDetailAttributes = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'transaction_type' => TransactionType::class,
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'admin_fee_rate' => 'decimal:4',
        'admin_fee_type' => AdminFeeType::class,
        'admin_fee_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $transaction): void {
            if ($transaction->pendingDetailAttributes === []) {
                return;
            }

            $attributes = $transaction->pendingDetailAttributes;
            $transaction->pendingDetailAttributes = [];

            $transaction->detail()->updateOrCreate([], $attributes);
            $transaction->unsetRelation('detail');
        });
    }

    /**
     * Intercept the tier-purchase-specific keys so callers can keep writing
     * them like regular attributes even though they now live on the related
     * `TierSubscriptionDetail` record.
     *
     * @param  array<string, mixed>  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach (self::DETAIL_KEYS as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->pendingDetailAttributes[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Get the user who initiated this transaction (e.g. registering a new school).
     *
     * @return BelongsTo<User, $this>
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the payment gateway used for this transaction.
     *
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    /**
     * Get the transaction's tier subscription detail record (school, subscription, misc metadata).
     *
     * @return HasOne<TierSubscriptionDetail, $this>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(TierSubscriptionDetail::class);
    }

    public function getSchoolIdAttribute(): ?string
    {
        return $this->detail?->school_id;
    }

    public function getSubscriptionIdAttribute(): ?string
    {
        return $this->detail?->subscription_id;
    }

    public function getSchoolAttribute(): ?School
    {
        return $this->detail?->school;
    }

    public function getSubscriptionAttribute(): ?SchoolTier
    {
        return $this->detail?->subscription;
    }

    public function getTierNameAttribute(): ?string
    {
        return $this->detail?->tier_name;
    }

    public function getBillingPeriodAttribute(): ?string
    {
        return $this->detail?->billing_period;
    }

    public function getFromTierIdAttribute(): ?int
    {
        return $this->detail?->from_tier_id;
    }

    public function getChangeTypeAttribute(): ?TierChangeType
    {
        return $this->detail?->change_type;
    }

    public function getProrationAmountAttribute(): ?string
    {
        return $this->detail?->proration_amount;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRegistrationDataAttribute(): ?array
    {
        return $this->detail?->registration_data;
    }
}
