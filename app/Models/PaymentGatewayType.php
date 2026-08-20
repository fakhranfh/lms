<?php

namespace App\Models;

use Database\Factories\PaymentGatewayTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'label', 'description', 'is_active'])]
class PaymentGatewayType extends Model
{
    /** @use HasFactory<PaymentGatewayTypeFactory> */
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
