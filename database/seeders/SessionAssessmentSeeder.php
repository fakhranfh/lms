<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Database\Seeder;

class SessionAssessmentSeeder extends Seeder
{
    /**
     * Link each course's assessments to one of its sessions. A session can
     * hold many assessments, so assessments are spread round-robin across
     * the course's sessions rather than all piling onto one.
     */
    public function run(): void
    {
        $courses = Course::whereHas('sessions')
            ->whereHas('assessments', fn ($query) => $query->whereNull('session_id'))
            ->get();

        foreach ($courses as $course) {
            $this->linkCourseAssessments($course);
        }
    }

    private function linkCourseAssessments(Course $course): void
    {
        $sessionIds = $course->sessions()->orderBy('date_start')->pluck('id');

        if ($sessionIds->isEmpty()) {
            return;
        }

        $unlinkedAssessments = Assessment::where('course_id', $course->id)
            ->whereNull('session_id')
            ->orderBy('created_at')
            ->get();

        foreach ($unlinkedAssessments as $index => $assessment) {
            $sessionId = $sessionIds[$index % $sessionIds->count()];

            $assessment->update(['session_id' => $sessionId]);
        }
    }
}
