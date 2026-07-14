<?php

namespace App\Models;

use Database\Factories\PaymentGatewayTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'label', 'description', 'is_active'])]
class PaymentGatewayType extends Model
{
    /** @use HasFactory<PaymentGatewayTypeFactory> */
    use HasFactory;
}
