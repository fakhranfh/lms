<?php

namespace App\Livewire\Courses\Concerns;

use App\Models\ForumThread;
use App\Services\CoursePersonService;
use App\Services\ForumCommentService;
use App\Services\ForumService;
use App\Services\ForumThreadService;
use App\Support\HtmlSanitizer;

trait HasForumMonitoringIndexDevTools
{
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
}
