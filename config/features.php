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

];
