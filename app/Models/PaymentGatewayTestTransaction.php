<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentGatewayTestTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_gateway_id', 'transaction_id', 'status', 'response'])]
class PaymentGatewayTestTransaction extends Model
{
    /** @use HasFactory<PaymentGatewayTestTransactionFactory> */
    use HasFactory, HasUuid;

    protected $casts = [
        'response' => 'array',
    ];

    /**
     * Get the payment gateway this dummy transaction belongs to.
     *
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
