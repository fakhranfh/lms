<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Services\ForumCommentLikeService;
use App\Services\ForumCommentService;
use App\Services\ForumThreadReadService;
use App\Services\ForumThreadService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Livewire\Component;

class ForumThreadShow extends Component
{
    public Course $course;

    public ForumThread $thread;

    public bool $commentsLoaded = false;

    public string $newCommentBody = '';

    public bool $editingThread = false;

    public string $editThreadTitle = '';

    public string $editThreadDescription = '';

    public ?string $editingCommentId = null;

    public string $editCommentBody = '';

    public ?string $replyingToCommentId = null;

    public string $newReplyBody = '';

    public function mount(CurrentSchool $currentSchool, Course $course, ForumThread $thread, ForumThreadReadService $forumThreadReadService): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('forum.view') && $course->school_id === $schoolId, 403);
        abort_unless($thread->forum->course_id === $course->id, 404);

        $this->course = $course;
        $this->thread = $thread;

        $forumThreadReadService->markRead($thread->id, auth()->id());
    }

    public function loadComments(): void
    {
        $this->commentsLoaded = true;
    }

    public function addComment(ForumCommentService $forumCommentService): void
    {
        abort_unless(auth()->user()->can('forum.create'), 403);

        $this->validate([
            'newCommentBody' => 'required|string|max:5000',
        ]);

        $forumCommentService->create([
            'thread_id' => $this->thread->id,
            'user_id' => auth()->id(),
            'body' => HtmlSanitizer::forum($this->newCommentBody),
        ]);

        $this->newCommentBody = '';
        $this->thread->refresh();
    }

    public function deleteComment(string $commentId, ForumCommentService $forumCommentService): void
    {
        $comment = $forumCommentService->find($commentId);

        if (! $comment) {
            return;
        }

        abort_unless($comment->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $forumCommentService->delete($commentId);
        $this->thread->refresh();
    }

    public function startEditComment(string $commentId, ForumCommentService $forumCommentService): void
    {
        $comment = $forumCommentService->find($commentId);

        if (! $comment) {
            return;
        }

        abort_unless($comment->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $this->editingCommentId = $comment->id;
        $this->editCommentBody = $comment->body;
    }

    public function cancelEditComment(): void
    {
        $this->editingCommentId = null;
        $this->editCommentBody = '';
        $this->resetErrorBag(['editCommentBody']);
    }

    public function updateComment(ForumCommentService $forumCommentService): void
    {
        abort_unless($this->editingCommentId !== null, 404);

        $comment = $forumCommentService->find($this->editingCommentId);

        if (! $comment) {
            $this->cancelEditComment();

            return;
        }

        abort_unless($comment->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $this->validate([
            'editCommentBody' => 'required|string|max:5000',
        ]);

        $forumCommentService->update($comment->id, [
            'body' => HtmlSanitizer::forum($this->editCommentBody),
        ]);

        $this->cancelEditComment();
    }

    public function startReply(string $commentId): void
    {
        $this->replyingToCommentId = $commentId;
        $this->newReplyBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingToCommentId = null;
        $this->newReplyBody = '';
        $this->resetErrorBag(['newReplyBody']);
    }

    public function addReply(ForumCommentService $forumCommentService): void
    {
        abort_unless(auth()->user()->can('forum.create'), 403);
        abort_unless($this->replyingToCommentId !== null, 404);

        $parent = $forumCommentService->find($this->replyingToCommentId);

        abort_unless($parent !== null && $parent->parent_id === null, 404);

        $this->validate([
            'newReplyBody' => 'required|string|max:5000',
        ]);

        $forumCommentService->create([
            'thread_id' => $this->thread->id,
            'parent_id' => $parent->id,
            'user_id' => auth()->id(),
            'body' => HtmlSanitizer::forum($this->newReplyBody),
        ]);

        $this->cancelReply();
        $this->thread->refresh();
    }

    public function startEditThread(): void
    {
        abort_unless($this->thread->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $this->editingThread = true;
        $this->editThreadTitle = $this->thread->title;
        $this->editThreadDescription = $this->thread->description;
    }

    public function cancelEditThread(): void
    {
        $this->editingThread = false;
        $this->editThreadTitle = '';
        $this->editThreadDescription = '';
        $this->resetErrorBag(['editThreadTitle', 'editThreadDescription']);
    }

    public function updateThread(ForumThreadService $forumThreadService): void
    {
        abort_unless($this->thread->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $this->validate([
            'editThreadTitle' => 'required|string|max:255',
            'editThreadDescription' => 'nullable|string',
        ]);

        $forumThreadService->update($this->thread->id, [
            'title' => $this->editThreadTitle,
            'description' => HtmlSanitizer::forum($this->editThreadDescription),
        ]);

        $this->thread->refresh();
        $this->cancelEditThread();
    }

    public function deleteThread(ForumThreadService $forumThreadService)
    {
        abort_unless($this->thread->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $forumThreadService->delete($this->thread->id);

        return redirect()->route('forum.index', [$this->course, 'session' => $this->thread->forum->session_id]);
    }

    public function render(ForumCommentService $forumCommentService, ForumCommentLikeService $forumCommentLikeService)
    {
        $comments = collect();
        $likedCommentIds = collect();

        if ($this->commentsLoaded) {
            $comments = $forumCommentService->topLevelForThread($this->thread->id, ['user', 'replies.user']);

            $allCommentIds = $comments->pluck('id')
                ->concat($comments->flatMap(fn (ForumComment $comment) => $comment->replies->pluck('id')))
                ->all();

            $likedCommentIds = $forumCommentLikeService->likedCommentIdsForUser($allCommentIds, auth()->id());
        }

        return view('livewire.courses.forum-thread-show', [
            'course' => $this->course,
            'thread' => $this->thread,
            'comments' => $comments,
            'likedCommentIds' => $likedCommentIds,
            'canCreate' => auth()->user()->can('forum.create'),
            'canModerate' => auth()->user()->can('forum.moderate'),
            'courseTabs' => CourseTabs::build($this->course, 'forum'),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
