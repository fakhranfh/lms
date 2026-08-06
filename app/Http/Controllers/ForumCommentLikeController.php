<?php

namespace App\Http\Controllers;

use App\Models\ForumComment;
use App\Services\ForumCommentLikeService;
use App\Support\CurrentSchool;
use Illuminate\Http\JsonResponse;

class ForumCommentLikeController extends Controller
{
    /**
     * Toggle the current user's like on a comment. Called via a plain fetch()
     * from the Forum thread view so liking stays instant and decoupled from
     * Livewire's full component re-render cycle.
     */
    public function toggle(ForumComment $comment, ForumCommentLikeService $forumCommentLikeService, CurrentSchool $currentSchool): JsonResponse
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('forum.create') && $comment->thread->forum->course->school_id === $schoolId, 403);

        $existing = $forumCommentLikeService->findByCommentAndUser($comment->id, auth()->id());

        if ($existing) {
            $forumCommentLikeService->delete($existing->id);
        } else {
            $forumCommentLikeService->create([
                'comment_id' => $comment->id,
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json([
            'liked' => $existing === null,
            'count' => $comment->fresh()->likes_count,
        ]);
    }
}
