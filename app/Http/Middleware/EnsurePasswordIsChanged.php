<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Redirect an authenticated user who still must change their password
     * (e.g. a student provisioned via a login link) to the forced
     * password-change page, unless they're already headed there or logging out.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user !== null
            && $user->must_change_password
            && ! $request->routeIs('password.force-change')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
