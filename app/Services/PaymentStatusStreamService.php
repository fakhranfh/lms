<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Http\Response as HttpResponse;
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
        return response()->stream(function () use ($transaction, $completedRedirectUrl): void {
            $deadline = microtime(true) + 10;

            while (microtime(true) < $deadline) {
                if (connection_aborted()) {
                    return;
                }

                $transaction->refresh();

                if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
                    $this->emit('completed', ['redirect_url' => $completedRedirectUrl]);

                    return;
                }

                if ($transaction->status === PaymentStatus::Failed) {
                    $this->emit('failed', []);

                    return;
                }

                echo ": keep-alive\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                usleep(500_000);
            }

            // Nothing happened within this connection's window — tell the
            // client to open a fresh one rather than leaving it hanging.
            $this->emit('timeout', []);
        }, HttpResponse::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
