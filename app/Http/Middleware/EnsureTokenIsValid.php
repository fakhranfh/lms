<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return redirect('/login')->withErrors(['email' => 'The password reset link is invalid.']);
        }

        // Cek di database apakah email ada
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        // Jika tidak ada atau token tidak cocok
        if (!$passwordReset || !Hash::check($token, $passwordReset->token)) {
            return redirect('/login')->withErrors(['email' => 'The password reset link is invalid or has already been used.']);
        }

        $expiresAt = strtotime($passwordReset->created_at) + (60 * 60); // 60 menit
        if (time() > $expiresAt) {
            return redirect('/login')->withErrors(['email' => 'The password reset link has expired.']);
        }

        return $next($request);
    }
}
