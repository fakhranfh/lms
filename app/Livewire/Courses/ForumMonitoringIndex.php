<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumCommentService;
use App\Services\ForumDiscussionScoringService;
use App\Services\ForumService;
use App\Services\ForumThreadService;
use App\Services\SessionService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
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

    /**
     * Dev-only helper to autofill 2 comments per student on the selected
     * session's forum, so a developer can quickly get every student past
     * the posting requirement while testing this monitoring page.
     */
    public function autofillComments(
        ForumService $forumService,
        ForumThreadService $forumThreadService,
        ForumCommentService $forumCommentService,
        CoursePersonService $coursePersonService,
    ): void {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('forum.moderate'), 403);
        abort_unless($this->sessionId !== null, 404);

        $forum = $forumService->findOrCreateForSession($this->sessionId, $this->course->id);

        $thread = $forumThreadService->get(['forum_id' => $forum->id])->first();

        if (! $thread) {
            $thread = $forumThreadService->create([
                'forum_id' => $forum->id,
                'user_id' => auth()->id(),
                'title' => 'Diskusi sesi ini',
                'description' => 'Silakan diskusikan materi sesi ini di sini.',
            ]);
        }

        /** @var ForumThread $thread */
        $students = $coursePersonService->studentsForCourse($this->course->id);
        $bodies = $this->fakeCommentBodies();
        $bodyIndex = 0;

        foreach ($students as $coursePerson) {
            for ($i = 0; $i < 2; $i++) {
                $forumCommentService->create([
                    'thread_id' => $thread->id,
                    'user_id' => $coursePerson->user_id,
                    'body' => HtmlSanitizer::forum($bodies[$bodyIndex % count($bodies)]),
                ]);

                $bodyIndex++;
            }
        }
    }

    /**
     * Dev-only helper to wipe every thread (and, via cascade, every
     * comment) in the selected session's forum, so a developer can reset
     * this monitoring page back to a clean slate between test runs.
     */
    public function deleteAllPosts(ForumService $forumService, ForumThreadService $forumThreadService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('forum.moderate'), 403);
        abort_unless($this->sessionId !== null, 404);

        $forum = $forumService->findOrCreateForSession($this->sessionId, $this->course->id);

        /** @var ForumThread $thread */
        foreach ($forumThreadService->get(['forum_id' => $forum->id]) as $thread) {
            $forumThreadService->delete($thread->id);
        }
    }

    /**
     * Realistic-sounding comment bodies for dev-only fake data generation —
     * deliberately not Lorem Ipsum so generated comments are easy to skim
     * while testing this monitoring page.
     *
     * @return array<int, string>
     */
    private function fakeCommentBodies(): array
    {
        return [
            'Setuju dengan poin ini, menurut saya penjelasannya sudah cukup jelas.',
            'Saya masih kurang paham di bagian ini, ada yang bisa jelaskan lebih lanjut?',
            'Terima kasih sudah dibagikan, ini sangat membantu untuk belajar.',
            'Menurut saya ada pendekatan lain yang lebih sederhana untuk kasus ini.',
            'Boleh minta contoh lain yang mirip dengan kasus ini?',
            'Saya sudah coba terapkan dan hasilnya sesuai dengan yang diharapkan.',
            'Ada referensi tambahan yang bisa dibaca untuk memperdalam topik ini?',
            'Saya rasa ini perlu didiskusikan lebih lanjut di sesi berikutnya.',
            'Poin bagus, saya sebelumnya belum kepikiran soal ini.',
            'Apakah ini juga berlaku untuk kasus yang sedikit berbeda?',
        ];
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
