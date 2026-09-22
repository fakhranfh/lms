<?php

use App\Http\Middleware\CheckFeatureAccess;
use App\Http\Middleware\EnsureLocalEnvironment;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\PreservePasswordUpdateErrors;
use App\Http\Middleware\RedirectIfNoSchool;
use App\Http\Middleware\RedirectLmsLocalToHttps;
use App\Http\Middleware\RequireSchool;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustReverseProxyScheme;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies));
        }

        $middleware->prepend(TrustReverseProxyScheme::class);
        $middleware->prepend(RedirectLmsLocalToHttps::class);
        $middleware->append(PreservePasswordUpdateErrors::class);
        $middleware->append(SecurityHeaders::class);

        // Payment gateway webhooks are called by Xendit/Midtrans directly,
        // without a browser session or CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->alias([
            'feature' => CheckFeatureAccess::class,
            'local-only' => EnsureLocalEnvironment::class,
            'must-change-password' => EnsurePasswordIsChanged::class,
            'permission' => PermissionMiddleware::class,
            'redirect-if-no-school' => RedirectIfNoSchool::class,
            'require-school' => RequireSchool::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
