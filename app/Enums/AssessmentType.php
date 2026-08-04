<?php

namespace App\Enums;

enum AssessmentType: string
{
    case TheoryPersonalAssignment = 'theory_personal_assignment';
    case TheoryTeamAssignment = 'theory_team_assignment';
    case TheoryQuiz = 'theory_quiz';
    case TheoryFinalExam = 'theory_final_exam';
    case Attendance = 'attendance';
    case ForumDiscussion = 'forum_discussion';

    public function defaultWeight(): float
    {
        return match ($this) {
            self::TheoryPersonalAssignment => 20.0,
            self::TheoryTeamAssignment => 15.0,
            self::TheoryQuiz => 15.0,
            self::TheoryFinalExam => 30.0,
            self::Attendance => 10.0,
            self::ForumDiscussion => 10.0,
        };
    }
}
