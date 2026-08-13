<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local dev only: force https:// for the app domain (and its subdomains) so
 * plain http:// links (bookmarks, typed URLs) always land on the TLS proxy
 * (see bin/https-proxy.sh) instead of the unencrypted artisan serve port.
 */
class RedirectLmsLocalToHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('app.domain');

        if (
            app()->environment('local')
            && $domain
            && ! $request->secure()
            && $request->header('X-Forwarded-Proto') !== 'https'
            && preg_match('/(^|\.)'.preg_quote($domain, '/').'$/', $request->getHost())
        ) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
