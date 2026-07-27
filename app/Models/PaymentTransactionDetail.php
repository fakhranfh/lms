<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentTransactionDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_transaction_id', 'school_id', 'subscription_id', 'metadata'])]
class PaymentTransactionDetail extends Model
{
    /** @use HasFactory<PaymentTransactionDetailFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
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
}
