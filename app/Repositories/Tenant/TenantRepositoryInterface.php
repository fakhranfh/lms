<?php

namespace App\Repositories\Tenant;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function create(array $data): Tenant;
}
