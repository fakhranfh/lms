<?php

namespace App\Enums;

enum ProctorReviewDecision: string
{
    case NoAction = 'no_action';
    case Warning = 'warning';
    case Disqualified = 'disqualified';
}
