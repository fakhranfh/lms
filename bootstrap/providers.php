<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuditLogServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    AuditLogServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
];
