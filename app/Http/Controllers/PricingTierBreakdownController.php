<?php

namespace App\Http\Controllers;

use App\Models\PricingTier;
use Illuminate\Http\JsonResponse;

class PricingTierBreakdownController extends Controller
{
    public function show(PricingTier $pricingTier): JsonResponse
    {
        abort_unless($pricingTier->is_active, 404);

        $subtotal = (float) $pricingTier->price;
        $vatRate = (float) config('billing.vat_rate');
        $adminFeeRate = (float) config('billing.admin_fee_rate');
        $vatAmount = $subtotal * $vatRate;
        $adminFeeAmount = $subtotal * $adminFeeRate;

        return response()->json([
            'id' => $pricingTier->id,
            'name' => $pricingTier->name,
            'billing_period' => strtolower($pricingTier->billing_period->label()),
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'admin_fee_rate' => $adminFeeRate,
            'admin_fee_amount' => $adminFeeAmount,
            'total' => $subtotal + $vatAmount + $adminFeeAmount,
        ]);
    }
}
