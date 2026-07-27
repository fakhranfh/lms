<?php

namespace App\Enums;

enum TransactionType: string
{
    case TierPurchase = 'tier_purchase';

    public function label(): string
    {
        return match ($this) {
            self::TierPurchase => 'Tier Purchase',
        };
    }
}
