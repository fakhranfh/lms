<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumDiscussionScoringService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AssessmentForumDiscussionShow extends Component
{
    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public function mount(
        CurrentSchool $currentSchool,
        CoursePersonService $coursePersonService,
        ?Course $course = null,
        ?Assessment $assessment = null,
    ): void {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::ForumDiscussion, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
        }

        $this->course = $course;
        $this->assessment = $assessment;
    }

    public function render(
        CoursePersonService $coursePersonService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
        GradebookScoringService $gradebookScoringService,
    ) {
        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'isStudent' => $this->isStudent,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        $onlineSessions = $forumDiscussionScoringService->sessionsInScope($this->assessment);

        if ($this->isStudent) {
            $forumDiscussionScoringService->recomputeForUser($this->assessment, auth()->id());
            $gradebookScoringService->recomputeForUser($this->course, auth()->id());

            $viewData['sessionRows'] = $onlineSessions->map(fn (Session $session) => [
                'session' => $session,
                'met' => $forumDiscussionScoringService->hasMetForumPostRequirement($session, auth()->id()),
                'required' => $forumDiscussionScoringService->requiredForumPosts($session),
            ]);
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            foreach ($students as $coursePerson) {
                $forumDiscussionScoringService->recomputeForUser($this->assessment, $coursePerson->user_id);
                $gradebookScoringService->recomputeForUser($this->course, $coursePerson->user_id);
            }

            $viewData['sessionRows'] = $onlineSessions->map(function (Session $session) use ($students, $forumDiscussionScoringService) {
                $metCount = $students->filter(
                    fn ($coursePerson) => $forumDiscussionScoringService->hasMetForumPostRequirement($session, $coursePerson->user_id)
                )->count();

                return [
                    'session' => $session,
                    'metCount' => $metCount,
                    'totalStudents' => $students->count(),
                    'required' => $forumDiscussionScoringService->requiredForumPosts($session),
                ];
            });
        }

        return view('livewire.courses.assessment-forum-discussion-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
