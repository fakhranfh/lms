<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Livewire\Courses\Concerns\HasForumMonitoringIndexDevTools;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\ForumComment;
use App\Models\ForumThread;
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
    use HasForumMonitoringIndexDevTools;
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

    public function deleteStudentThread(string $threadId, ForumThreadService $forumThreadService): void
    {
        abort_unless(auth()->user()->can('forum.moderate'), 403);

        $thread = $forumThreadService->find($threadId, ['forum']);

        if ($thread && $thread->forum->session_id === $this->sessionId) {
            $forumThreadService->delete($threadId);
        }
    }

    public function deleteStudentComment(string $commentId, ForumCommentService $forumCommentService): void
    {
        abort_unless(auth()->user()->can('forum.moderate'), 403);

        $comment = $forumCommentService->find($commentId, ['thread.forum']);

        if ($comment && $comment->thread->forum->session_id === $this->sessionId) {
            $forumCommentService->delete($commentId);
        }
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

        $required = $forumDiscussionScoringService->requiredForumPosts($selectedSession);

        $rows = $students->forPage($page, $this->perPage)->map(function (CoursePerson $coursePerson) use ($selectedSession, $forumThreadService, $forumCommentService, $required) {
            $threads = $forumThreadService->forUserInSession($coursePerson->user_id, $selectedSession->id);
            $comments = $forumCommentService->forUserInSession($coursePerson->user_id, $selectedSession->id, ['thread']);

            $totalPosts = $threads->count() + $comments->count();

            return [
                'user' => $coursePerson->user,
                'threadCount' => $threads->count(),
                'commentCount' => $comments->count(),
                'totalPosts' => $totalPosts,
                'met' => $totalPosts >= $required,
                'remaining' => max(0, $required - $totalPosts),
                'threadsJson' => $threads->map(fn (ForumThread $thread) => [
                    'id' => $thread->id,
                    'title' => $thread->title,
                    'createdAt' => $thread->created_at_display->format('d M Y, H:i'),
                ])->values()->all(),
                'commentsJson' => $comments->map(fn (ForumComment $comment) => [
                    'id' => $comment->id,
                    'threadId' => $comment->thread_id,
                    'body' => str($comment->body)->stripTags()->limit(200)->toString(),
                    'threadTitle' => $comment->thread->title,
                    'createdAt' => $comment->created_at_display->format('d M Y, H:i'),
                ])->values()->all(),
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
