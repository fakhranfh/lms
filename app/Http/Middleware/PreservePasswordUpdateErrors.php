<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreservePasswordUpdateErrors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->getMethod() === 'PUT' && $request->path() === 'user/password') {
            // If response is a redirect and there are session errors, redirect to change-password
            if ($response->status() === 302) {
                if ($request->session()->has('errors')) {
                    $response->headers->set('Location', url('/change-password'));
                }
            }
        }

        return $response;
    }
}
