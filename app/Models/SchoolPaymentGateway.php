<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\SchoolPaymentGatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'gateway_type_id', 'is_enabled', 'is_sandbox_mode', 'webhook_secret'])]
class SchoolPaymentGateway extends Model
{
    /** @use HasFactory<SchoolPaymentGatewayFactory> */
    use BelongsToTenant, HasFactory, HasUuid;

    protected $table = 'tenant_payment_gateways';

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
        return $this->hasMany(PaymentGatewayCredential::class, 'school_payment_gateway_id');
    }
}
