<?php

namespace App\Services;

use App\Models\School;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentGatewayType\PaymentGatewayTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PaymentGatewayRegistry
{
    private const CACHE_KEY = 'payment_gateway_types';

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly PaymentGatewayTypeRepositoryInterface $gatewayTypeRepository,
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function getGatewayTypes(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->gatewayTypeRepository->getActive();
        });
    }

    public function getSchoolGateways(School $school): Collection
    {
        return $this->gatewayRepository->getEnabledForSchool($school->id, ['paymentGatewayType', 'credentials']);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
