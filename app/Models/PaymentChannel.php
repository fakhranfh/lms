<?php

namespace App\Models;

use App\Services\R2StorageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string|null $logo_url
 */
#[Fillable(['code', 'label', 'brand_color', 'logo_url'])]
class PaymentChannel extends Model
{
    protected $table = 'payment_channels';

    /**
     * Build the logo URL from the stored R2 key rather than the persisted
     * value, so links keep working if the R2 base/custom domain changes later.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ? app(R2StorageService::class)->getPublicUrl(app(R2StorageService::class)->extractKeyFromPath($value))
                : $value,
        );
    }
}
