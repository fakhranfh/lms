<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGatewayConfigRequest;
use App\Http\Requests\UpdateGatewayConfigRequest;
use App\Models\SchoolPaymentGateway;
use App\Services\PaymentGatewayConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GatewayConfigController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayConfigService $gatewayConfigService
    ) {}

    public function index(): View
    {
        $gateways = $this->gatewayConfigService->getAllGateways(['paymentGatewayType', 'credentials']);

        return view('admin.gateways.index', [
            'gateways' => $gateways,
        ]);
    }

    public function create(): View
    {
        $gateways = $this->gatewayConfigService->getAvailableGatewayTypes();

        return view('admin.gateways.create', ['gateways' => $gateways]);
    }

    public function store(StoreGatewayConfigRequest $request): RedirectResponse
    {
        $this->gatewayConfigService->createGateway($request->validated());

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway configured successfully.');
    }

    public function edit(SchoolPaymentGateway $gateway): View
    {
        $gateway->load(['paymentGatewayType', 'credentials']);
        $allGateways = $this->gatewayConfigService->getAvailableGatewayTypes();

        $credentials = [];
        foreach ($gateway->credentials as $cred) {
            $credentials[$cred->credential_key] = $cred->credential_value;
        }

        return view('admin.gateways.edit', [
            'gateway' => $gateway,
            'allGateways' => $allGateways,
            'credentials' => $credentials,
        ]);
    }

    public function update(UpdateGatewayConfigRequest $request, SchoolPaymentGateway $gateway): RedirectResponse
    {
        $this->gatewayConfigService->updateGateway($gateway->id, $request->validated());

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway updated successfully.');
    }

    public function destroy(SchoolPaymentGateway $gateway): RedirectResponse
    {
        $this->gatewayConfigService->deleteGateway($gateway->id);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway removed successfully.');
    }

    public function testConnection(SchoolPaymentGateway $gateway): RedirectResponse
    {
        $result = $this->gatewayConfigService->testConnection($gateway);

        $redirectKey = $result['success'] ? 'success' : 'error';

        return redirect()->back()
            ->with($redirectKey, $result['message']);
    }
}
