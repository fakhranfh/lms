<?php

namespace App\Http\Controllers\Admin;

use App\Enums\XenditChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGatewayConfigRequest;
use App\Http\Requests\UpdateGatewayConfigRequest;
use App\Models\PaymentGateway;
use App\Services\PaymentGatewayConfigService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayConfigController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayConfigService $gatewayConfigService,
        private readonly QrCodeService $qrCodeService
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

    public function edit(PaymentGateway $gateway): View
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

    public function update(UpdateGatewayConfigRequest $request, PaymentGateway $gateway): RedirectResponse
    {
        $this->gatewayConfigService->updateGateway($gateway->id, $request->validated());

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway updated successfully.');
    }

    public function destroy(PaymentGateway $gateway): RedirectResponse
    {
        $this->gatewayConfigService->deleteGateway($gateway->id);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway removed successfully.');
    }

    public function testConnection(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $channel = $request->input('channel');

        $result = $this->gatewayConfigService->testConnection($gateway, $channel);

        if ($result['success'] && $channel !== null) {
            return redirect()->route('admin.gateways.test-result', $gateway);
        }

        $redirectKey = $result['success'] ? 'success' : 'error';

        return redirect()->back()
            ->with($redirectKey, $result['message']);
    }

    public function testResult(PaymentGateway $gateway): View|RedirectResponse
    {
        $gateway->load(['paymentGatewayType', 'testTransaction']);
        $testTransaction = $gateway->testTransaction;

        if (! $testTransaction) {
            return redirect()->route('admin.gateways.index')
                ->with('error', 'No test transaction found. Please run a connection test first.');
        }

        $response = $testTransaction->response ?? [];
        $channelValue = $response['channel'] ?? null;
        $channel = $channelValue ? XenditChannel::from($channelValue) : null;

        $qrCodeSvg = ($channel?->viewType() === 'qris' && ($response['payment_url'] ?? null))
            ? $this->qrCodeService->svg($response['payment_url'])
            : null;

        return view('admin.gateways.test-result.'.($channel?->viewType() ?? 'generic'), [
            'gateway' => $gateway,
            'channel' => $channel,
            'response' => $response,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }
}
