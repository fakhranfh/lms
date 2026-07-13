<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use App\Support\TenantDomainResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    public function __construct(
        private CurrentTenant $currentTenant,
        private TenantDomainResolver $tenantDomainResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $rootDomain = config('app.domain');
        $adminDomain = "admin.{$rootDomain}";

        if ($request->user() && $request->user()->hasRole('admin') && $request->user()->tenant_id !== null) {
            abort(500, 'Invalid state: admin user must not belong to a tenant.');
        }

        if ($host === $adminDomain) {
            $this->currentTenant->setTenantId(null);

            if ($request->user() && ! $request->user()->hasRole('admin')) {
                abort(403);
            }

            return $next($request);
        }

        if ($host === $rootDomain) {
            $this->currentTenant->setTenantId(null);

            return $next($request);
        }

        $tenant = $this->tenantDomainResolver->resolve($request);

        if (! $tenant) {
            abort(404);
        }

        $this->currentTenant->setTenantId($tenant->id);

        return $next($request);
    }
}
