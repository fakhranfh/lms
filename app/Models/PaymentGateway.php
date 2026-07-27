<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['gateway_type_id', 'is_enabled', 'is_sandbox_mode', 'webhook_secret'])]
class PaymentGateway extends Model
{
    /** @use HasFactory<PaymentGatewayFactory> */
    use HasFactory, HasUuid;

    protected $table = 'payment_gateways';

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_sandbox_mode' => 'boolean',
    ];

    /**
     * Get the payment gateway type.
     *
     * @return BelongsTo<PaymentGatewayType, $this>
     */
    public function paymentGatewayType(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewayType::class, 'gateway_type_id');
    }

    /**
     * Get the gateway credentials.
     *
     * @return HasMany<PaymentGatewayCredential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(PaymentGatewayCredential::class, 'payment_gateway_id');
    }
}
