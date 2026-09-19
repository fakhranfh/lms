<?php

return [
    /*
    |--------------------------------------------------------------------|
    | Login link TTL for teachers
    |--------------------------------------------------------------------|
    |
    | How long (in minutes) a generated teacher login link stays valid.
    | Defaults to 2 days (2880 minutes).
    |
    */
    'login_link_ttl_minutes' => (int) env('TEACHER_LOGIN_LINK_TTL_MINUTES', 2880),
];
