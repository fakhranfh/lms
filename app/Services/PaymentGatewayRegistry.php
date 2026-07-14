<?php

namespace App\Services;

use App\Models\PaymentGatewayType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PaymentGatewayRegistry
{
    private const CACHE_KEY = 'payment_gateway_types';

    private const CACHE_TTL = 3600; // 1 hour

    public function getGatewayTypes(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return PaymentGatewayType::where('is_active', true)->get();
        });
    }

    public function getSchoolGateways(Tenant $school): Collection
    {
        return $school->paymentGateways()
            ->where('is_enabled', true)
            ->with('paymentGatewayType', 'credentials')
            ->get();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
