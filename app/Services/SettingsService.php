<?php

namespace App\Services;

use App\Enums\AdminFeeType;
use App\Models\Setting;

class SettingsService
{
    private const VAT_RATE_KEY = 'billing.vat_rate';

    private const ADMIN_FEE_TYPE_KEY = 'billing.admin_fee_type';

    private const ADMIN_FEE_RATE_KEY = 'billing.admin_fee_rate';

    private const ADMIN_FEE_NOMINAL_KEY = 'billing.admin_fee_nominal';

    public function getVatRate(): float
    {
        return (float) Setting::get(self::VAT_RATE_KEY, (string) config('billing.vat_rate'));
    }

    public function getAdminFeeType(): AdminFeeType
    {
        return AdminFeeType::from(Setting::get(self::ADMIN_FEE_TYPE_KEY, AdminFeeType::Percentage->value));
    }

    public function getAdminFeeRate(): float
    {
        return (float) Setting::get(self::ADMIN_FEE_RATE_KEY, (string) config('billing.admin_fee_rate'));
    }

    public function getAdminFeeNominal(): float
    {
        return (float) Setting::get(self::ADMIN_FEE_NOMINAL_KEY, '0');
    }

    /**
     * Compute the admin fee amount for a given subtotal, based on the
     * currently configured admin fee type (percentage of subtotal, or a
     * fixed nominal amount).
     */
    public function calculateAdminFee(float $subtotal): float
    {
        return $this->getAdminFeeType() === AdminFeeType::Fixed
            ? $this->getAdminFeeNominal()
            : $subtotal * $this->getAdminFeeRate();
    }

    public function updateBillingRates(float $vatRate, AdminFeeType $adminFeeType, float $adminFeeRate, float $adminFeeNominal): void
    {
        Setting::set(self::VAT_RATE_KEY, (string) $vatRate);
        Setting::set(self::ADMIN_FEE_TYPE_KEY, $adminFeeType->value);
        Setting::set(self::ADMIN_FEE_RATE_KEY, (string) $adminFeeRate);
        Setting::set(self::ADMIN_FEE_NOMINAL_KEY, (string) $adminFeeNominal);
    }
}
