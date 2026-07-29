<?php

namespace App\Http\Controllers;

use App\Enums\AdminFeeType;
use App\Models\PricingTier;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class PricingTierBreakdownController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function show(PricingTier $pricingTier): JsonResponse
    {
        abort_unless($pricingTier->is_active, 404);

        $subtotal = (float) $pricingTier->price;
        $vatRate = $this->settingsService->getVatRate();
        $adminFeeType = $this->settingsService->getAdminFeeType();
        $adminFeeRate = $this->settingsService->getAdminFeeRate();
        $vatAmount = $subtotal * $vatRate;
        $adminFeeAmount = $this->settingsService->calculateAdminFee($subtotal);

        return response()->json([
            'id' => $pricingTier->id,
            'name' => $pricingTier->name,
            'billing_period' => strtolower($pricingTier->billing_period->label()),
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'admin_fee_type' => $adminFeeType->value,
            'admin_fee_rate' => $adminFeeType === AdminFeeType::Percentage ? $adminFeeRate : 0,
            'admin_fee_amount' => $adminFeeAmount,
            'total' => $subtotal + $vatAmount + $adminFeeAmount,
        ]);
    }
}
