<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'label', 'brand_color', 'logo_url'])]
class PaymentChannel extends Model
{
    protected $table = 'payment_channels';
}
