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

            $session = $sessionService->create([
                'course_id' => $this->course->id,
                'title' => fake()->sentence(4),
                'learning_outcome' => fake()->paragraph(),
                'date_start' => $dateStart,
                'date_end' => (clone $dateStart)->addDays(6),
                'delivery_mode' => DeliveryMode::cases()[array_rand(DeliveryMode::cases())]->value,
                'order' => $nextOrder + $i,
            ]);

            foreach (range(1, random_int(2, 4)) as $subtopicOrder) {
                $sessionSubtopicService->create([
                    'session_id' => $session->id,
                    'subtopic' => fake()->sentence(4),
                    'order' => $subtopicOrder,
                ]);
            }

            $material = $this->generateDummyPdfMaterial($session, $mediaLibraryService);
            $session->materials()->sync([$material->id => ['order' => 1]]);
        }

        $this->successMessage = __(':count sessions generated.', ['count' => $count]);
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
