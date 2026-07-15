<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook routes - publicly accessible with rate limiting
Route::middleware(['throttle:100,1', 'throttle:1000,60'])->group(function () {
    Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])->name('webhooks.midtrans');
    Route::post('/webhooks/xendit', [PaymentWebhookController::class, 'handleXendit'])->name('webhooks.xendit');
});
