<?php

namespace App\Enums;

enum ProctorSessionStatus: string
{
    case Active = 'active';
    case Submitting = 'submitting';
    case Completed = 'completed';
    case Terminated = 'terminated';
}
