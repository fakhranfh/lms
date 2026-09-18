<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-tertiary-container text-on-tertiary-container',
            self::Completed => 'bg-primary-container text-on-primary-container',
            self::Failed => 'bg-error-container text-on-error-container',
            self::Refunded => 'bg-outline-variant text-on-surface',
        };
    }
}
