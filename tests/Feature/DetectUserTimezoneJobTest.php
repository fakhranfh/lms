<?php

use App\Jobs\DetectUserTimezoneJob;
use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

test('updates user timezone when detection succeeds', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $user = User::factory()->create(['timezone' => 'UTC']);

    (new DetectUserTimezoneJob($user->id, '8.8.8.8'))->handle(app(IpGeolocationService::class));

    expect($user->fresh()->timezone)->toBe('Asia/Jakarta');
});

test('does nothing when the user no longer exists', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    (new DetectUserTimezoneJob((string) Str::uuid(), '8.8.8.8'))->handle(app(IpGeolocationService::class));

    Http::assertNothingSent();
});

test('leaves timezone unchanged when detection fails', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('', 500),
        'ip-api.com/*' => Http::response('', 500),
    ]);

    $user = User::factory()->create(['timezone' => 'UTC']);

    (new DetectUserTimezoneJob($user->id, '8.8.8.8'))->handle(app(IpGeolocationService::class));

    expect($user->fresh()->timezone)->toBe('UTC');
});
