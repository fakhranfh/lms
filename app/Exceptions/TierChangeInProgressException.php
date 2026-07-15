<?php

namespace App\Exceptions;

use Exception;

class TierChangeInProgressException extends Exception
{
    public function __construct(string $message = 'A tier change is already in progress for this school.')
    {
        parent::__construct($message);
    }
}
