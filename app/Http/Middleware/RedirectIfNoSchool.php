<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Support\RootDomains;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNoSchool
{
    /**
     * Redirect School Admins who don't administer any school yet to the
     * "register your school" step of the get-started flow.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (
            $user
            && ! $user->hasRole(RoleName::Admin)
            && $user->schools()->doesntExist()
            && $user->memberSchools()->doesntExist()
        ) {
            $suffix = RootDomains::suffixFor(RootDomains::match($request->getHost()));

            return redirect()->route("get-started.school{$suffix}")
                ->with('status', 'You need to register a school before you can continue.');
        }

        return $next($request);
    }
}
