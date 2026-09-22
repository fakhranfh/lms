<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Session;
use App\Services\MediaLibraryService;
use App\Services\SessionService;
use App\Services\SessionSubtopicService;
use App\Services\VideoConferenceService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SessionForm extends Component
{
    public Course $course;

    public ?Session $session = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $learningOutcome = '';

    #[Validate('required|date')]
    public string $dateStart = '';

    #[Validate('required|date|after_or_equal:dateStart')]
    public string $dateEnd = '';

    #[Validate('required|in:online,offline,virtual_class')]
    public string $deliveryMode = 'online';

    /** @var array<int, string> */
    #[Validate(['subtopics.*' => 'required|string|max:255'])]
    public array $subtopics = [];

    /** @var array<int, string> */
    public array $selectedMaterialIds = [];

    public string $materialSearch = '';

    /**
     * @var array<int, array{title: string, scheduled_start_at: string, scheduled_end_at: string, meeting_url: string, required_duration_minutes: string}>
     */
    public array $videoConferences = [];

    public function mount(?Course $course = null, ?Session $session = null): void
    {
        abort_unless(auth()->user()->can('sessions.create') || auth()->user()->can('sessions.edit'), 403);

        $course ??= $session?->course;

        abort_if($course === null, 404);

        $schoolId = auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);

        $this->course = $course;

        if ($session) {
            $this->session = $session;
            $this->title = $session->title;
            $this->learningOutcome = $session->learning_outcome ?? '';
            $this->dateStart = $session->date_start->format('Y-m-d\TH:i');
            $this->dateEnd = $session->date_end->format('Y-m-d\TH:i');
            $this->deliveryMode = $session->delivery_mode->value;
            $this->subtopics = $session->subtopics->pluck('subtopic')->all();
            $this->selectedMaterialIds = $session->materials->pluck('id')->all();
            $this->videoConferences = $session->videoConferences->map(fn ($videoConference) => [
                'title' => $videoConference->title ?? '',
                'scheduled_start_at' => $videoConference->scheduled_start_at->format('Y-m-d\TH:i'),
                'scheduled_end_at' => $videoConference->scheduled_end_at->format('Y-m-d\TH:i'),
                'meeting_url' => $videoConference->meeting_url ?? '',
                'required_duration_minutes' => (string) ($videoConference->required_duration_minutes ?? ''),
            ])->all();
        }
    }

    /**
     * @return array{url?: string, key?: string, error?: string}
     */
    public function generateMaterialUploadUrl(string $filename, string $materialType, MediaLibraryService $mediaLibraryService): array
    {
        abort_unless(auth()->user()->can('media.create'), 403);

        try {
            $type = MaterialType::tryFrom($materialType);
            if (! $type) {
                return ['error' => 'Invalid material type'];
            }

            return $mediaLibraryService->generatePresignedUploadUrl($this->course->school_id, $filename, $materialType);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Finalizes a material uploaded directly from the session form (rather
     * than picked from the media library) and adds it to the selection.
     *
     * @param  array<string, mixed>  $data
     * @return array{id?: string, title?: string, type?: string, error?: string}
     */
    public function finalizeMaterialUpload(array $data, MediaLibraryService $mediaLibraryService): array
    {
        abort_unless(auth()->user()->can('media.create'), 403);

        try {
            $item = $mediaLibraryService->finalizeUpload($this->course->school_id, auth()->id(), $data);

            $this->selectedMaterialIds[] = $item->id;

            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type->value,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function addVideoConference(): void
    {
        $this->videoConferences[] = [
            'title' => '',
            'scheduled_start_at' => '',
            'scheduled_end_at' => '',
            'meeting_url' => '',
            'required_duration_minutes' => '',
        ];
    }

    public function removeVideoConference(int $index): void
    {
        unset($this->videoConferences[$index]);
        $this->videoConferences = array_values($this->videoConferences);
    }

    public function save(
        SessionService $sessionService,
        SessionSubtopicService $sessionSubtopicService,
        VideoConferenceService $videoConferenceService,
    ) {
        try {
            return $this->persist($sessionService, $sessionSubtopicService, $videoConferenceService);
        } catch (\Throwable $exception) {
            $this->dispatch('sessionform-error');

            throw $exception;
        }
    }

    private function persist(
        SessionService $sessionService,
        SessionSubtopicService $sessionSubtopicService,
        VideoConferenceService $videoConferenceService,
    ) {
        $this->validate();

        $data = [
            'course_id' => $this->course->id,
            'title' => $this->title,
            'learning_outcome' => $this->learningOutcome ?: null,
            'date_start' => $this->dateStart,
            'date_end' => $this->dateEnd,
            'delivery_mode' => $this->deliveryMode,
        ];

        if ($this->session) {
            $sessionService->update($this->session->id, $data);
            $session = $this->session;

            foreach ($session->subtopics as $existing) {
                $sessionSubtopicService->delete($existing->id);
            }

            foreach ($session->videoConferences as $existing) {
                $videoConferenceService->delete($existing->id);
            }

            $eventName = 'session-updated';
        } else {
            $session = $sessionService->create($data);
            $eventName = 'session-created';
        }

        foreach (array_values(array_filter($this->subtopics, fn ($subtopic) => trim($subtopic) !== '')) as $index => $subtopic) {
            $sessionSubtopicService->create([
                'session_id' => $session->id,
                'subtopic' => $subtopic,
                'order' => $index + 1,
            ]);
        }

        foreach ($this->videoConferences as $videoConference) {
            if (blank($videoConference['title']) && blank($videoConference['scheduled_start_at'])) {
                continue;
            }

            $videoConferenceService->create([
                'session_id' => $session->id,
                'title' => $videoConference['title'] ?: null,
                'scheduled_start_at' => $videoConference['scheduled_start_at'],
                'scheduled_end_at' => $videoConference['scheduled_end_at'],
                'meeting_url' => $videoConference['meeting_url'] ?: null,
                'required_duration_minutes' => $videoConference['required_duration_minutes'] !== ''
                    ? (int) $videoConference['required_duration_minutes']
                    : null,
            ]);
        }

        $materialSync = [];
        foreach (array_values($this->selectedMaterialIds) as $index => $materialId) {
            $materialSync[$materialId] = ['order' => $index + 1];
        }
        $session->materials()->sync($materialSync);

        $this->dispatch($eventName);

        return redirect()->route('sessions.index', $this->course);
    }

    public function render(MediaLibraryService $mediaLibraryService)
    {
        $schoolId = $this->course->school_id;
        $extensionTypeMap = MaterialType::extensionTypeMap();

        return view('livewire.courses.session-form', [
            'pageTitle' => $this->session ? 'Edit Session' : 'Create Session',
            'deliveryModes' => DeliveryMode::cases(),
            'mediaItems' => Collection::make($mediaLibraryService->list($schoolId, null, $this->materialSearch ?: null)->get()),
            'selectedMediaItems' => $this->selectedMaterialIds === []
                ? Collection::make()
                : MediaLibraryItem::whereIn('id', $this->selectedMaterialIds)->get(),
            'extensionTypeMap' => $extensionTypeMap,
            'acceptedExtensions' => implode(',', array_map(fn (string $ext) => ".{$ext}", array_keys($extensionTypeMap))),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->session ? 'Edit Session' : 'Create Session'])
            ->section('app-content');
    }
}
