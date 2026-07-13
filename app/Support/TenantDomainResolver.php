<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantDomainResolver
{
    public function resolve(Request $request): ?Tenant
    {
        return Tenant::where('domain', $request->getHost())->first();
    }
}
