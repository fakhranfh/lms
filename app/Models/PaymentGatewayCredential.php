<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentGatewayCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_gateway_id', 'credential_key', 'credential_value', 'is_sensitive'])]
class PaymentGatewayCredential extends Model
{
    /** @use HasFactory<PaymentGatewayCredentialFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'credential_value' => 'encrypted',
        'is_sensitive' => 'boolean',
    ];

    /**
     * Get the school payment gateway.
     *
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
