<?php

namespace App\Http\Controllers;

use App\Services\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentWebhookService $webhookService
    ) {}

    public function handleMidtrans(Request $request): Response
    {
        return $this->handleWebhook($request, 'midtrans');
    }

    public function handleXendit(Request $request): Response
    {
        return $this->handleWebhook($request, 'xendit');
    }

    private function handleWebhook(Request $request, string $gatewayName): Response
    {
        $result = $this->webhookService->handleWebhook($request, $gatewayName);

        return response($result['message'], $result['status']);
    }
}
