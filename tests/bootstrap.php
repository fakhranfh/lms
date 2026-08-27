<?php

$cachedConfig = __DIR__.'/../bootstrap/cache/config.php';

if (file_exists($cachedConfig)) {
    unlink($cachedConfig);
}

require __DIR__.'/../vendor/autoload.php';
