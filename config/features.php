<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Features
    |--------------------------------------------------------------------------
    |
    | Controls whether SMTP-email-dependent features are active: Fortify's
    | email verification, password reset emails, and the profile pending
    | email change confirmation. Disable this when no mail server is
    | configured, then re-enable it later without touching any code.
    |
    */

    'email_enabled' => env('FEATURE_EMAIL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Server-Sent Events
    |--------------------------------------------------------------------------
    |
    | Controls whether real-time endpoints stream updates over Server-Sent
    | Events. Some hosting setups (certain shared hosts, proxies that buffer
    | responses) don't support long-lived streamed connections, so this lets
    | those endpoints fall back to client-side AJAX polling instead.
    |
    */

    'sse_enabled' => env('FEATURE_SSE_ENABLED', true),

];
