<?php

namespace App\Exceptions;

use Exception;

class FeatureNotAvailableException extends Exception
{
    public function __construct(string $message = 'This feature is not available in your subscription tier.')
    {
        parent::__construct($message);
    }
}
