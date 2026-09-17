<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\ForumCommentService;
use App\Support\HtmlSanitizer;

trait HasForumThreadShowDevTools
{
    /**
     * Dev-only toggle to bypass the "session must be ongoing" restriction on
     * editing/deleting threads and comments, so a developer can test those
     * actions outside the scheduled session window. Enforced server-side via
     * canEditOrDelete()'s environment check, so tampering with this property
     * client-side has no effect outside local/testing.
     */
    public bool $devBypassEditDelete = false;

    /**
     * Dev-only helper to bulk-create fake top-level comments for this
     * thread, so a developer can quickly populate data for testing
     * pagination, sorting, or the forum monitoring page without posting by
     * hand.
     */
    public function generateComments(ForumCommentService $forumCommentService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('forum.create'), 403);

        $count = max(1, min(50, $this->generateCommentCount));
        $bodies = $this->fakeCommentBodies();

        for ($i = 0; $i < $count; $i++) {
            $forumCommentService->create([
                'thread_id' => $this->thread->id,
                'user_id' => auth()->id(),
                'body' => HtmlSanitizer::forum($bodies[$i % count($bodies)]),
            ]);
        }

        $this->thread->refresh();
        $this->dispatch('comment-updated');
    }

    /**
     * Realistic-sounding comment bodies for dev-only fake data generation —
     * deliberately not Lorem Ipsum so generated comments are easy to skim
     * while testing pagination, sorting, or moderation.
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
