<?php

namespace App\Enums;

enum CourseMembershipStatus: string
{
    case Active = 'active';
    case Dropped = 'dropped';
    case Completed = 'completed';
}
