<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = Tenant::create($request->validated());

        $port = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";
        $registerUrl = "{$request->getScheme()}://{$tenant->domain}{$portSuffix}/register";

        return redirect()->away($registerUrl)
            ->with('status', "School registered! Go to {$registerUrl} to create your account.");
    }
}
