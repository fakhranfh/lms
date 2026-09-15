<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumCommentService;
use App\Services\ForumDiscussionScoringService;
use App\Services\ForumThreadService;
use App\Services\SessionService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ForumMonitoringIndex extends Component
{
    use WithPagination;

    private const DEFAULT_STUDENTS_PER_PAGE = 10;

    public Course $course;

    public bool $dataLoaded = false;

    #[Url(as: 'session')]
    public ?string $sessionId = null;

    #[Url(as: 'q')]
    public string $studentSearch = '';

    #[Url(as: 'perPage')]
    public int $perPage = self::DEFAULT_STUDENTS_PER_PAGE;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('forum.moderate') && $course->school_id === $schoolId, 403);

        $this->course = $course;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function updatingStudentSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function render(
        SessionService $sessionService,
        ForumThreadService $forumThreadService,
        ForumCommentService $forumCommentService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
        CoursePersonService $coursePersonService,
    ) {
        $viewData = [
            'course' => $this->course,
            'courseTabs' => CourseTabs::build($this->course, 'forum'),
            'teacher' => null,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.forum-monitoring-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $onlineSessions = $sessionService->forCourse($this->course->id)
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::Online)
            ->values();

        $selectedSession = ($this->sessionId ? $onlineSessions->firstWhere('id', $this->sessionId) : null)
            ?? $onlineSessions->first();

        $viewData['selectedSession'] = $selectedSession;

        if ($selectedSession) {
            $this->sessionId = $selectedSession->id;

            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);

            if ($search !== '') {
                $students = $students->filter(
                    fn (CoursePerson $coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $viewData['required'] = $forumDiscussionScoringService->requiredForumPosts($selectedSession);
            $viewData['studentRows'] = $this->paginateStudentRows(
                $students,
                $selectedSession,
                $forumThreadService,
                $forumCommentService,
                $forumDiscussionScoringService,
            );
        }

        return view('livewire.courses.forum-monitoring-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     */
    private function paginateStudentRows(
        Collection $students,
        Session $selectedSession,
        ForumThreadService $forumThreadService,
        ForumCommentService $forumCommentService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
    ): LengthAwarePaginator {
        $page = $this->getPage();

        $rows = $students->forPage($page, $this->perPage)->map(function (CoursePerson $coursePerson) use ($selectedSession, $forumThreadService, $forumCommentService, $forumDiscussionScoringService) {
            $threadCount = $forumThreadService->countForUserInSession($coursePerson->user_id, $selectedSession->id);
            $commentCount = $forumCommentService->countForUserInSession($coursePerson->user_id, $selectedSession->id);

            return [
                'user' => $coursePerson->user,
                'threadCount' => $threadCount,
                'commentCount' => $commentCount,
                'totalPosts' => $threadCount + $commentCount,
                'met' => $forumDiscussionScoringService->hasMetForumPostRequirement($selectedSession, $coursePerson->user_id),
            ];
        })->values();

        return new LengthAwarePaginator(
            $rows,
            $students->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }
}
