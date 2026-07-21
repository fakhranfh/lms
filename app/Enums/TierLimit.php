<?php

namespace App\Enums;

enum TierLimit: string
{
    case MaterialStorageGb = 'material_storage_gb';

    public function label(): string
    {
        return match ($this) {
            self::MaterialStorageGb => 'Material Storage (GB)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MaterialStorageGb => 'Total storage allocation for course materials (videos, PDFs, images, etc.)',
        };
    }
}
