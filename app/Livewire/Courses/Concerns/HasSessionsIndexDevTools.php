<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Models\MediaLibraryItem;
use App\Models\Session;
use App\Services\MediaLibraryService;
use App\Services\SessionService;
use App\Services\SessionSubtopicService;

trait HasSessionsIndexDevTools
{
    /**
     * Dev-only: bulk-generates dummy sessions for the current course so the
     * UI can be exercised without manually filling in the create form.
     */
    public function devGenerateSessions(
        SessionService $sessionService,
        SessionSubtopicService $sessionSubtopicService,
        MediaLibraryService $mediaLibraryService,
    ): void {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('sessions.create'), 403);

        $count = max(1, min(50, $this->generateCount));
        $nextOrder = $sessionService->forCourse($this->course->id)->max(fn ($session) => $session->order) ?? 0;
        $start = now()->addDay();

        for ($i = 1; $i <= $count; $i++) {
            $dateStart = (clone $start)->addDays(($i - 1) * 7);
            $topic = $this->dummySessionTopics()[($nextOrder + $i - 1) % count($this->dummySessionTopics())];

            $session = $sessionService->create([
                'course_id' => $this->course->id,
                'title' => "Sesi {$i}: {$topic['title']}",
                'learning_outcome' => $topic['outcome'],
                'date_start' => $dateStart,
                'date_end' => (clone $dateStart)->addDays(6),
                'delivery_mode' => DeliveryMode::cases()[array_rand(DeliveryMode::cases())]->value,
                'order' => $nextOrder + $i,
            ]);

            foreach ($topic['subtopics'] as $subtopicOrder => $subtopic) {
                $sessionSubtopicService->create([
                    'session_id' => $session->id,
                    'subtopic' => $subtopic,
                    'order' => $subtopicOrder + 1,
                ]);
            }

            $material = $this->generateDummyPdfMaterial($session, $mediaLibraryService);
            $session->materials()->sync([$material->id => ['order' => 1]]);
        }

        $this->successMessage = __(':count sessions generated.', ['count' => $count]);
    }

    /**
     * Realistic session topics used to seed dev-generated sessions, so the
     * generated content reads like an actual course outline instead of
     * placeholder Lorem ipsum text.
     *
     * @return array<int, array{title: string, outcome: string, subtopics: array<int, string>}>
     */
    private function dummySessionTopics(): array
    {
        return [
            [
                'title' => 'Pengenalan dan Ruang Lingkup Materi',
                'outcome' => 'Peserta mampu menjelaskan tujuan, ruang lingkup, dan garis besar materi yang akan dipelajari selama kelas berlangsung.',
                'subtopics' => [
                    'Gambaran umum topik pembelajaran',
                    'Tujuan dan target capaian kelas',
                    'Aturan main dan skema penilaian',
                ],
            ],
            [
                'title' => 'Konsep Dasar dan Terminologi',
                'outcome' => 'Peserta mampu memahami konsep dasar serta istilah-istilah kunci yang akan digunakan sepanjang pembelajaran.',
                'subtopics' => [
                    'Definisi dan istilah penting',
                    'Contoh penerapan di dunia nyata',
                    'Latihan pemahaman konsep',
                ],
            ],
            [
                'title' => 'Studi Kasus dan Analisis Masalah',
                'outcome' => 'Peserta mampu menganalisis studi kasus terkait materi dan mengidentifikasi permasalahan yang relevan.',
                'subtopics' => [
                    'Pemaparan studi kasus',
                    'Diskusi kelompok terkait permasalahan',
                    'Presentasi hasil analisis',
                ],
            ],
            [
                'title' => 'Praktik dan Implementasi',
                'outcome' => 'Peserta mampu menerapkan konsep yang telah dipelajari ke dalam latihan praktik secara langsung.',
                'subtopics' => [
                    'Demonstrasi langkah praktik',
                    'Sesi latihan mandiri',
                    'Sesi tanya jawab dan umpan balik',
                ],
            ],
            [
                'title' => 'Evaluasi dan Refleksi Pembelajaran',
                'outcome' => 'Peserta mampu mengevaluasi pemahaman diri terhadap materi dan merefleksikan proses belajar yang telah dilalui.',
                'subtopics' => [
                    'Kuis singkat pemahaman materi',
                    'Diskusi refleksi kelompok',
                    'Rangkuman dan penutup sesi',
                ],
            ],
            [
                'title' => 'Kolaborasi dan Kerja Kelompok',
                'outcome' => 'Peserta mampu bekerja sama dalam kelompok untuk menyelesaikan tugas terkait materi pembelajaran.',
                'subtopics' => [
                    'Pembagian kelompok dan peran',
                    'Pengerjaan tugas kelompok',
                    'Presentasi hasil kerja kelompok',
                ],
            ],
        ];
    }

    /**
     * Uploads a distinct dummy PDF to R2 and creates its MediaLibraryItem, so
     * every dev-generated session gets its own material file rather than all
     * sharing a single reused row.
     */
    private function generateDummyPdfMaterial(Session $session, MediaLibraryService $mediaLibraryService): MediaLibraryItem
    {
        $content = "%PDF-1.4\n"
            .'1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj'."\n"
            .'2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj'."\n"
            .'3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Resources<<>>/Contents 4 0 R>>endobj'."\n"
            .'4 0 obj<</Length 44>>stream'."\n"
            ."BT /F1 18 Tf 20 100 Td ({$session->title}) Tj ET".
            "\nendstream endobj\n"
            .'trailer<</Size 5/Root 1 0 R>>'."\n"
            .'%%EOF';

        $key = "media/{$this->course->school_id}/dev-generated/{$session->id}.pdf";

        return $mediaLibraryService->createFromRawContent(
            $this->course->school_id,
            auth()->id(),
            $key,
            $content,
            'application/pdf',
            [
                'type' => MaterialType::PDF->value,
                'title' => "Material - {$session->title}",
                'description' => 'Dev-generated dummy PDF material.',
            ],
        );
    }
}
