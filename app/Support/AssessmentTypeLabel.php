<?php

namespace App\Support;

use App\Enums\AssessmentType;

class AssessmentTypeLabel
{
    public static function forType(AssessmentType $type): string
    {
        return match ($type) {
            AssessmentType::TheoryPersonalAssignment => 'Personal Assignment',
            AssessmentType::TheoryTeamAssignment => 'Team Assignment',
            AssessmentType::TheoryQuiz => 'Quiz',
            AssessmentType::TheoryFinalExam => 'Final Exam',
            AssessmentType::Attendance => 'Attendance',
            AssessmentType::ForumDiscussion => 'Forum Discussion',
        };
    }
}
