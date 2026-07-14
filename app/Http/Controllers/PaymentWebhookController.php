<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentWebhook;
use App\Models\PaymentGatewayType;
use App\Models\PaymentWebhook;
use App\Models\SchoolPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function handleMidtrans(Request $request): Response
    {
        return $this->storeWebhook($request, 'midtrans');
    }

    public function handleXendit(Request $request): Response
    {
        return $this->storeWebhook($request, 'xendit');
    }

    private function storeWebhook(Request $request, string $gatewayName): Response
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response('Invalid webhook payload', Response::HTTP_BAD_REQUEST);
        }

        $eventType = $this->extractEventType($payload, $gatewayName);
        if (! $eventType) {
            return response('Invalid webhook payload', Response::HTTP_BAD_REQUEST);
        }

        $gatewayType = PaymentGatewayType::where('name', $gatewayName)->first();
        if (! $gatewayType) {
            return response('Gateway not found', Response::HTTP_NOT_FOUND);
        }

        $schoolPaymentGateway = $this->findSchoolGateway($payload, $gatewayType);
        if (! $schoolPaymentGateway) {
            return response('School gateway not found', Response::HTTP_NOT_FOUND);
        }

        $webhook = PaymentWebhook::create([
            'school_payment_gateway_id' => $schoolPaymentGateway->id,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'processed' => false,
        ]);

        ProcessPaymentWebhook::dispatch($webhook);

        return response('Webhook received', Response::HTTP_OK);
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

    private function findSchoolGateway(array $payload, PaymentGatewayType $gatewayType): ?SchoolPaymentGateway
    {
        $schoolId = $this->extractSchoolId($payload);
        if (! $schoolId) {
            return null;
        }

        return SchoolPaymentGateway::where('school_id', $schoolId)
            ->where('gateway_type_id', $gatewayType->id)
            ->first();
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
