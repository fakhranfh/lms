<?php

namespace App\Enums;

enum FinalExamType: string
{
    case OpenBook = 'open_book';
    case ClosedBook = 'closed_book';
    case TakeHome = 'take_home';
}
