<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGatewayConfigRequest;
use App\Http\Requests\UpdateGatewayConfigRequest;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;
use App\Models\SchoolPaymentGateway;
use App\Services\CredentialEncryption;
use App\Services\PaymentGatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GatewayConfigController extends Controller
{
    public function __construct(
        private readonly CredentialEncryption $credentialEncryption,
        private readonly PaymentGatewayFactory $gatewayFactory
    ) {}

    public function index(): View
    {
        $gateways = SchoolPaymentGateway::with(['paymentGatewayType', 'credentials'])
            ->whereNull('school_id')
            ->orWhereColumn('school_id', '!=', 'school_id')
            ->get();

        $availableGateways = PaymentGatewayType::where('is_active', true)->get();

        return view('admin.gateways.index', [
            'gateways' => $gateways,
            'availableGateways' => $availableGateways,
        ]);
    }

    public function create(): View
    {
        $gateways = PaymentGatewayType::where('is_active', true)->get();

        return view('admin.gateways.create', ['gateways' => $gateways]);
    }

    public function store(StoreGatewayConfigRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $gateway = SchoolPaymentGateway::create([
            'gateway_type_id' => $data['gateway_type_id'],
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
        ]);

        foreach ($data['credentials'] as $key => $value) {
            if ($value !== null && $value !== '') {
                PaymentGatewayCredential::create([
                    'school_payment_gateway_id' => $gateway->id,
                    'credential_key' => $key,
                    'credential_value' => $value,
                    'is_sensitive' => true,
                ]);
            }
        }

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway configured successfully.');
    }

    public function edit(SchoolPaymentGateway $gateway): View
    {
        $gateway->load(['paymentGatewayType', 'credentials']);
        $allGateways = PaymentGatewayType::where('is_active', true)->get();

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
        $data = $request->validated();

        $gateway->update([
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
        ]);

        $gateway->credentials()->delete();

        foreach ($data['credentials'] as $key => $value) {
            if ($value !== null && $value !== '') {
                PaymentGatewayCredential::create([
                    'school_payment_gateway_id' => $gateway->id,
                    'credential_key' => $key,
                    'credential_value' => $value,
                    'is_sensitive' => true,
                ]);
            }
        }

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway updated successfully.');
    }

    public function destroy(SchoolPaymentGateway $gateway): RedirectResponse
    {
        $gateway->credentials()->delete();
        $gateway->delete();

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Payment gateway removed successfully.');
    }

    public function testConnection(SchoolPaymentGateway $gateway): RedirectResponse
    {
        try {
            $gateway->load(['paymentGatewayType', 'credentials']);

            $gatewayInstance = $this->gatewayFactory->make(
                $gateway->paymentGatewayType->name,
                $gateway
            );

            $result = $gatewayInstance->checkTransactionStatus('test-transaction');

            if ($result && isset($result['success']) && ! $result['success']) {
                return redirect()->back()
                    ->with('warning', 'Gateway responded but with an error. Please check your credentials.');
            }

            return redirect()->back()
                ->with('success', 'Gateway connection successful!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Connection failed: '.$e->getMessage());
        }
    }
}
