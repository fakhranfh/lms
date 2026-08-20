<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['gateway_type_id', 'is_enabled', 'is_sandbox_mode', 'webhook_secret', 'enabled_channels'])]
class PaymentGateway extends Model
{
    /** @use HasFactory<PaymentGatewayFactory> */
    use HasFactory, HasUuid;

    protected $table = 'payment_gateways';

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_sandbox_mode' => 'boolean',
        'enabled_channels' => 'array',
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

    /**
     * Get the dummy transaction used to test this gateway's connection.
     *
     * @return HasOne<PaymentGatewayTestTransaction, $this>
     */
    public function testTransaction(): HasOne
    {
        return $this->hasOne(PaymentGatewayTestTransaction::class, 'payment_gateway_id');
    }
}
