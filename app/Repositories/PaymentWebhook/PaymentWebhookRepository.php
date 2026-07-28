<?php

namespace App\Repositories\PaymentWebhook;

use App\Models\PaymentWebhook;

class PaymentWebhookRepository implements PaymentWebhookRepositoryInterface
{
    public function create(array $data): PaymentWebhook
    {
        return PaymentWebhook::create([...$data, 'created_at' => now()]);
    }
}
