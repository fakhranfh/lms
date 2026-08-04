<?php

namespace App\Enums;

enum QuizScoringMethod: string
{
    case Highest = 'highest';
    case Latest = 'latest';
    case Average = 'average';
}
