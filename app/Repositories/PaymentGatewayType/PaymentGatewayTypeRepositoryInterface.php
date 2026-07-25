<?php

namespace App\Repositories\PaymentGatewayType;

use App\Models\PaymentGatewayType;
use Illuminate\Database\Eloquent\Collection;

interface PaymentGatewayTypeRepositoryInterface
{
    public function findByName(string $name): ?PaymentGatewayType;

    public function getActive(): Collection;
}
