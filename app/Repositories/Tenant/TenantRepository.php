<?php

namespace App\Repositories\Tenant;

use App\Models\Tenant;

class TenantRepository implements TenantRepositoryInterface
{
    public function create(array $data): Tenant
    {
        return Tenant::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
        ]);
    }
}
