<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Support\Sse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentStatusStreamService
{
    /**
     * Server-Sent Events stream: pushes a "completed"/"failed" event to the
     * browser as soon as the transaction's status changes, instead of the
     * client re-polling on a fixed interval. Each connection self-checks the
     * DB every 500ms and closes after ~10s either way — the browser's
     * EventSource reconnects automatically, so this stays event-driven from
     * the client's point of view without holding one PHP request open
     * indefinitely (this app's local `php artisan serve` has no pcntl, so it
     * can't handle concurrent requests while one is blocked long-term).
     */
    public function stream(PaymentTransaction $transaction, string $completedRedirectUrl): StreamedResponse
    {
        return Sse::response(function () use ($transaction, $completedRedirectUrl): void {
            $deadline = microtime(true) + 10;

            while (microtime(true) < $deadline) {
                if (connection_aborted()) {
                    return;
                }

                $transaction->refresh();

                if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
                    Sse::emit('completed', ['redirect_url' => $completedRedirectUrl]);

                    return;
                }

                if ($transaction->status === PaymentStatus::Failed) {
                    Sse::emit('failed', []);

                    return;
                }

                Sse::keepAlive();

                usleep(500_000);
            }

            // Nothing happened within this connection's window — tell the
            // client to open a fresh one rather than leaving it hanging.
            Sse::emit('timeout', []);
        });
    }
}
