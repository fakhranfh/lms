<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProctorSpeedTestController extends Controller
{
    /**
     * Serve a fixed-size random payload for client-side download throughput
     * measurement during the proctoring pre-flight speed check.
     */
    public function download(): Response
    {
        $bytes = 4 * 1024 * 1024; // 4 MB

        return response(random_bytes($bytes), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $bytes,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Accept a fixed-size payload from the client for upload throughput
     * measurement. The body isn't used; the client times the round trip.
     */
    public function upload(Request $request): Response
    {
        return response('', 204);
    }
}
