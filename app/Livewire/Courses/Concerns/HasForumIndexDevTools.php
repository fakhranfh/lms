<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\ForumThreadService;
use App\Support\HtmlSanitizer;

trait HasForumIndexDevTools
{
    /**
     * Dev-only helper to bulk-create fake threads for the current session's
     * forum, so a developer can quickly populate data for testing pagination,
     * moderation, or the forum monitoring page without posting by hand.
     */
    public function generateThreads(ForumThreadService $forumThreadService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('forum.create'), 403);
        abort_unless($this->currentForumId !== null, 404);

        $count = max(1, min(50, $this->generateThreadCount));
        $topics = $this->fakeThreadTopics();

        for ($i = 0; $i < $count; $i++) {
            $topic = $topics[$i % count($topics)];

            $forumThreadService->create([
                'forum_id' => $this->currentForumId,
                'user_id' => auth()->id(),
                'title' => $topic['title'].' #'.($i + 1),
                'description' => HtmlSanitizer::forum($topic['description']),
            ]);
        }

        $this->page = 1;
        $this->dispatch('thread-created');
    }

    /**
     * Realistic-sounding thread title/description pairs for dev-only fake
     * data generation — deliberately not Lorem Ipsum so generated threads
     * are easy to skim while testing pagination, moderation, or monitoring.
     *
     * @return array<int, array{title: string, description: string}>
     */
    private function fakeThreadTopics(): array
    {
        return [
            ['title' => 'Pertanyaan tentang materi minggu ini', 'description' => 'Ada bagian materi yang belum saya pahami sepenuhnya, apakah ada yang bisa menjelaskan ulang dengan contoh sederhana?'],
            ['title' => 'Diskusi tugas kelompok', 'description' => 'Mari kita samakan pembagian tugas kelompok di sini supaya tidak ada yang tumpang tindih sebelum deadline.'],
            ['title' => 'Kesulitan memahami konsep dasar', 'description' => 'Saya masih bingung dengan konsep dasar yang dijelaskan di sesi ini, mohon bantuan teman-teman untuk berdiskusi.'],
            ['title' => 'Berbagi catatan belajar', 'description' => 'Saya sudah merangkum poin-poin penting dari sesi ini, silakan cek dan tambahkan kalau ada yang terlewat.'],
            ['title' => 'Tanya jawab sebelum ujian', 'description' => 'Sebelum ujian minggu depan, ada yang mau tanya-jawab soal materi yang sering keluar di latihan soal?'],
            ['title' => 'Review sesi sebelumnya', 'description' => 'Menurut kalian bagian mana dari sesi sebelumnya yang paling sulit dipahami? Yuk kita bahas bersama.'],
            ['title' => 'Rekomendasi sumber belajar tambahan', 'description' => 'Ada rekomendasi video atau artikel tambahan yang membantu memahami topik ini lebih dalam?'],
            ['title' => 'Klarifikasi deadline tugas', 'description' => 'Mohon konfirmasi apakah deadline tugas untuk sesi ini tetap sesuai jadwal atau ada perubahan.'],
            ['title' => 'Sharing pengalaman praktik', 'description' => 'Saya baru saja mencoba menerapkan materi ini langsung, mau berbagi pengalaman sekaligus tanya pendapat kalian.'],
            ['title' => 'Diskusi studi kasus', 'description' => 'Bagaimana pendapat kalian tentang studi kasus yang diberikan di sesi ini? Ada pendekatan lain yang lebih efektif?'],
        ];
    }
}
