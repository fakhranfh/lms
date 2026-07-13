<?php

use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Http;

test('returns null for private or missing ip', function () {
    $service = new IpGeolocationService;

    expect($service->detectTimezone(null))->toBeNull();
    expect($service->detectTimezone('127.0.0.1'))->toBeNull();
    expect($service->detectTimezone('192.168.1.1'))->toBeNull();
});

test('returns timezone for a valid public ip', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('8.8.8.8'))->toBe('Asia/Jakarta');
});

test('returns null when api responds with an invalid timezone', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Invalid Response', 200),
        'ip-api.com/*' => Http::response(['status' => 'fail'], 200),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('8.8.8.8'))->toBeNull();
});

test('returns null when both providers fail', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('', 500),
        'ip-api.com/*' => Http::response('', 500),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('8.8.8.8'))->toBeNull();
});

test('falls back to ip-api.com when ipapi.co fails', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('', 429),
        'ip-api.com/*' => Http::response(['status' => 'success', 'timezone' => 'Asia/Jakarta'], 200),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('8.8.8.8'))->toBe('Asia/Jakarta');
});

test('falls back to public ip lookup for private ip in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    Http::fake([
        'api.ipify.org' => Http::response('8.8.8.8', 200),
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('127.0.0.1'))->toBe('Asia/Jakarta');
});

test('does not fall back to public ip lookup for private ip outside local environment', function () {
    Http::fake([
        'api.ipify.org' => Http::response('8.8.8.8', 200),
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $service = new IpGeolocationService;

    expect($service->detectTimezone('127.0.0.1'))->toBeNull();
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ipify'));
});
