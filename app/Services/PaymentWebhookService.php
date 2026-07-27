<?php

namespace App\Services;

use App\Jobs\ProcessPaymentWebhook;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentGatewayType\PaymentGatewayTypeRepositoryInterface;
use App\Repositories\PaymentWebhook\PaymentWebhookRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookService
{
    public function __construct(
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
        private readonly PaymentGatewayTypeRepositoryInterface $gatewayTypeRepository,
        private readonly PaymentWebhookRepositoryInterface $webhookRepository,
    ) {}

    public function handleWebhook(Request $request, string $gatewayName): array
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid webhook payload',
            ];
        }

        $eventType = $this->extractEventType($payload, $gatewayName);
        if (! $eventType) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid webhook payload',
            ];
        }

        $gatewayType = $this->gatewayTypeRepository->findByName($gatewayName);
        if (! $gatewayType) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Gateway not found',
            ];
        }

        $paymentGateway = $this->gatewayRepository->findByGatewayType($gatewayType->id);
        if (! $paymentGateway) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Gateway not found',
            ];
        }

        $webhook = $this->webhookRepository->create([
            'payment_gateway_id' => $paymentGateway->id,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'processed' => false,
        ]);

        ProcessPaymentWebhook::dispatch($webhook);

        return [
            'status' => Response::HTTP_OK,
            'message' => 'Webhook received',
        ];
    }

    private function extractEventType(array $payload, string $gatewayName): ?string
    {
        if ($gatewayName === 'midtrans') {
            if (isset($payload['transaction_status'])) {
                return 'transaction.'.strtolower($payload['transaction_status']);
            }
        } elseif ($gatewayName === 'xendit') {
            if (isset($payload['status'])) {
                return 'invoice.'.strtolower($payload['status']);
            }
        }

        return null;
    }
}
