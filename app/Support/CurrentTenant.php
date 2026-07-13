<?php

namespace App\Support;

class CurrentTenant
{
    private ?string $tenantId = null;

    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }
}
