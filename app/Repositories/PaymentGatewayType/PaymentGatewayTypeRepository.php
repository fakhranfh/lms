<?php

namespace App\Repositories\PaymentGatewayType;

use App\Models\PaymentGatewayType;
use Illuminate\Database\Eloquent\Collection;

class PaymentGatewayTypeRepository implements PaymentGatewayTypeRepositoryInterface
{
    public function findByName(string $name): ?PaymentGatewayType
    {
        return PaymentGatewayType::where('name', $name)->first();
    }

    public function getActive(): Collection
    {
        return PaymentGatewayType::where('is_active', true)->get();
    }
}
