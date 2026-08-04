<?php

namespace App\Enums;

enum RoleInCourse: string
{
    case Teacher = 'teacher';
    case Assistant = 'assistant';
    case Student = 'student';
}
