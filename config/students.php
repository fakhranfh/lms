<?php

return [
    /*
    |--------------------------------------------------------------------|
    | Login link TTL for students
    |--------------------------------------------------------------------|
    |
    | How long (in minutes) a generated student login link stays valid.
    | Defaults to 2 days (2880 minutes).
    |
    */
    'login_link_ttl_minutes' => (int) env('STUDENT_LOGIN_LINK_TTL_MINUTES', 2880),
];
