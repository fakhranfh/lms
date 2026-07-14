<?php

namespace App\Services;

use App\Jobs\ProcessPaymentWebhook;
use App\Models\PaymentGatewayType;
use App\Models\PaymentWebhook;
use App\Repositories\SchoolPaymentGateway\SchoolPaymentGatewayRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookService
{
    public function __construct(
        private readonly SchoolPaymentGatewayRepositoryInterface $gatewayRepository
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

        $gatewayType = PaymentGatewayType::where('name', $gatewayName)->first();
        if (! $gatewayType) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Gateway not found',
            ];
        }

        $schoolPaymentGateway = $this->findSchoolGateway($payload, $gatewayType);
        if (! $schoolPaymentGateway) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'School gateway not found',
            ];
        }

        $webhook = PaymentWebhook::create([
            'school_payment_gateway_id' => $schoolPaymentGateway->id,
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

    private function findSchoolGateway(array $payload, PaymentGatewayType $gatewayType)
    {
        $schoolId = $this->extractSchoolId($payload);
        if (! $schoolId) {
            return null;
        }

        return $this->gatewayRepository->findBySchoolAndGatewayType($schoolId, $gatewayType->id);
    }

    private function extractSchoolId(array $payload): ?string
    {
        if (isset($payload['custom_field1'])) {
            $metadata = json_decode($payload['custom_field1'], true);

            return $metadata['school_id'] ?? null;
        }

        if (isset($payload['metadata']['school_id'])) {
            return $payload['metadata']['school_id'];
        }

        return null;
    }
}
