<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\Session;
use App\Services\MediaLibraryService;
use App\Services\SessionService;
use App\Services\SessionSubtopicService;
use App\Services\VideoConferenceService;
use App\Support\CurrentSchool;
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

    #[Validate('required|in:online,offline')]
    public string $deliveryMode = 'online';

    /** @var array<int, string> */
    #[Validate(['subtopics.*' => 'required|string|max:255'])]
    public array $subtopics = [];

    /** @var array<int, string> */
    public array $selectedMaterialIds = [];

    /**
     * @var array<int, array{title: string, scheduled_start_at: string, scheduled_end_at: string, meeting_url: string, required_duration_minutes: string}>
     */
    public array $videoConferences = [];

    public function mount(CurrentSchool $currentSchool, ?Course $course = null, ?Session $session = null): void
    {
        abort_unless(auth()->user()->can('sessions.create') || auth()->user()->can('sessions.edit'), 403);

        $course ??= $session?->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
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

    public function addSubtopic(): void
    {
        $this->subtopics[] = '';
    }

    public function removeSubtopic(int $index): void
    {
        unset($this->subtopics[$index]);
        $this->subtopics = array_values($this->subtopics);
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

        return view('livewire.courses.session-form', [
            'pageTitle' => $this->session ? 'Edit Session' : 'Create Session',
            'deliveryModes' => DeliveryMode::cases(),
            'mediaItems' => Collection::make($mediaLibraryService->list($schoolId)->get()),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->session ? 'Edit Session' : 'Create Session'])
            ->section('app-content');
    }
}
