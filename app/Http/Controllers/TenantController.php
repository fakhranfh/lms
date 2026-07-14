<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    public function __construct(private TenantService $tenantService) {}

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        $registerUrl = $this->tenantService->buildRegisterUrl(
            $tenant,
            $request->getScheme(),
            $request->getPort()
        );

        return redirect()->away($registerUrl)
            ->with('status', "School registered! Go to {$registerUrl} to create your account.");
    }
}
