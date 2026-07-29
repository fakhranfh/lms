<?php

namespace App\Services;

use App\Jobs\ProcessPaymentWebhook;
use App\Models\PaymentGateway;
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

        // Xendit's callback token only exists on the original HTTP request
        // (the "X-Callback-Token" header), so it must be verified here,
        // synchronously, before the payload is handed off to the queued job
        // — a queued job has no access to the request that dispatched it.
        if ($gatewayName === 'xendit' && ! $this->verifyXenditCallbackToken($request, $paymentGateway)) {
            return [
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Invalid webhook signature',
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

    /**
     * Verifies the callback token when the gateway has one configured. If no
     * webhook_secret has been set on the gateway, verification is skipped —
     * there's nothing to check it against, and rejecting every webhook in
     * that case would silently break payment processing rather than fail
     * safely. Admins should still set a webhook_secret to get real
     * verification.
     */
    private function verifyXenditCallbackToken(Request $request, PaymentGateway $paymentGateway): bool
    {
        $expected = $paymentGateway->webhook_secret ?? '';

        if ($expected === '') {
            return true;
        }

        return hash_equals($expected, (string) $request->header('X-Callback-Token'));
    }

    private function extractEventType(array $payload, string $gatewayName): ?string
    {
        if ($gatewayName === 'midtrans') {
            if (isset($payload['transaction_status'])) {
                return 'transaction.'.strtolower($payload['transaction_status']);
            }
        } elseif ($gatewayName === 'xendit') {
            // v3 webhooks send {"event": "payment.succeeded", "data": {...}}
            if (isset($payload['event'])) {
                return $payload['event'];
            }
        }

        return null;
    }
}
