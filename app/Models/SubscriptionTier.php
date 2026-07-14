<?php

namespace App\Models;

use Database\Factories\SubscriptionTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description', 'price', 'currency', 'billing_period', 'features', 'max_users', 'storage_gb', 'is_active'])]
class SubscriptionTier extends Model
{
    /** @use HasFactory<SubscriptionTierFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];
}
