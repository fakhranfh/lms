<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a local TLS-terminating proxy (see bin/https-proxy.sh) forwards a
 * request to the plain-HTTP dev server, this makes Laravel treat the
 * request as secure and generate https:// URLs, so links, CSRF/session
 * cookies, and asset URLs (including Livewire's) match the page's origin.
 *
 * Not gated on app()->environment('local') because this app intentionally
 * runs with APP_ENV=production locally; the proxy's X-Forwarded-Proto
 * header is trusted regardless of environment.
 */
class TrustReverseProxyScheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Forwarded-Proto') === 'https') {
            $request->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
