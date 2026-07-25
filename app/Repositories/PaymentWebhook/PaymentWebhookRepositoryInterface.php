<?php

namespace App\Repositories\PaymentWebhook;

use App\Models\PaymentWebhook;

interface PaymentWebhookRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentWebhook;
}
