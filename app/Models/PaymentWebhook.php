<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentWebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_gateway_id', 'event_type', 'payload', 'processed', 'processed_at', 'created_at'])]
class PaymentWebhook extends Model
{
    /** @use HasFactory<PaymentWebhookFactory> */
    use HasFactory, HasUuid;

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'encrypted',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
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
