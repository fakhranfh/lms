<?php

namespace App\Support;

use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared plumbing for Server-Sent Events streams. Wraps the headers, event
 * framing, and flush calls that every SSE endpoint in this app needs, so
 * individual streams only implement their own polling/termination logic.
 */
class Sse
{
    public static function response(callable $callback, int $status = HttpResponse::HTTP_OK): StreamedResponse
    {
        return response()->stream($callback, $status, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_SLASHES)."\n\n";

        self::flush();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function emitData(array $data): void
    {
        echo 'data: '.json_encode($data, JSON_UNESCAPED_SLASHES)."\n\n";

        self::flush();
    }

    public static function keepAlive(): void
    {
        echo ": keep-alive\n\n";

        self::flush();
    }

    private static function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
