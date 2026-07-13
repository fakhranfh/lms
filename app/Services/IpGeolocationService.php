<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IpGeolocationService
{
    /**
     * Resolve an IANA timezone identifier from a public IP address.
     */
    public function detectTimezone(?string $ip): ?string
    {
        if ($ip && ! $this->isPublicIp($ip) && App::environment('local')) {
            $ip = $this->resolvePublicIp();
        }

        if (! $ip || ! $this->isPublicIp($ip)) {
            return null;
        }

        return $this->lookupViaIpapi($ip) ?? $this->lookupViaIpApiCom($ip);
    }

    private function lookupViaIpapi(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/timezone/");

            $timezone = trim($response->body());

            if ($response->successful() && in_array($timezone, timezone_identifiers_list(), true)) {
                return $timezone;
            }
        } catch (Throwable $e) {
            Log::warning('IP timezone lookup via ipapi.co failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function lookupViaIpApiCom(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,timezone',
            ]);

            $timezone = $response->json('timezone');

            if ($response->successful() && $response->json('status') === 'success' && in_array($timezone, timezone_identifiers_list(), true)) {
                return $timezone;
            }
        } catch (Throwable $e) {
            Log::warning('IP timezone lookup via ip-api.com failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Resolve the machine's own public IP address, used as a stand-in for
     * the request IP when developing locally (where the request IP is
     * always a private/loopback address).
     */
    private function resolvePublicIp(): ?string
    {
        try {
            $response = Http::timeout(3)->get('https://api.ipify.org');

            $ip = trim($response->body());

            if ($response->successful() && $this->isPublicIp($ip)) {
                return $ip;
            }
        } catch (Throwable $e) {
            Log::warning('Public IP lookup failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
