<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local dev only: when a local TLS-terminating proxy (see bin/https-proxy.sh)
 * forwards a request to the plain-HTTP dev server, this makes Laravel treat
 * the request as secure and generate https:// URLs, so links, CSRF/session
 * cookies, and asset URLs (including Livewire's) match the page's origin.
 */
class TrustReverseProxyScheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && $request->header('X-Forwarded-Proto') === 'https') {
            $request->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
