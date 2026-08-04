<?php

namespace App\Enums;

enum ProctorSessionStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Terminated = 'terminated';
}
