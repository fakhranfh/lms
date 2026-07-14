<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasUuid;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'subscription_id', 'school_payment_gateway_id', 'transaction_id', 'amount', 'currency', 'status', 'metadata'])]
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use BelongsToSchool, HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription.
     *
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the school payment gateway.
     *
     * @return BelongsTo<SchoolPaymentGateway, $this>
     */
    public function schoolPaymentGateway(): BelongsTo
    {
        return $this->belongsTo(SchoolPaymentGateway::class, 'school_payment_gateway_id');
    }
}
