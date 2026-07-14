<?php

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Tenant\TenantRepositoryInterface;

class TenantService
{
    public function __construct(private TenantRepositoryInterface $tenantRepository) {}

    public function create(array $data): Tenant
    {
        return $this->tenantRepository->create($data);
    }

    public function buildRegisterUrl(Tenant $tenant, string $scheme, int $port): string
    {
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";

        return "{$scheme}://{$tenant->domain}{$portSuffix}/register";
    }
}
