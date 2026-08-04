<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Models\Course;
use App\Services\SessionService;
use App\Support\CurrentSchool;
use Livewire\Component;

class SessionsIndex extends Component
{
    public Course $course;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isStudent = false;

    /** @var array<string, bool> */
    public array $expandedSessions = [];

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('sessions.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function toggleSession(string $sessionId): void
    {
        $this->expandedSessions[$sessionId] = ! ($this->expandedSessions[$sessionId] ?? false);
    }

    public function confirmDelete(string $sessionId, SessionService $sessionService): void
    {
        abort_unless(auth()->user()->can('sessions.delete'), 403);

        $session = $sessionService->find($sessionId);

        if (! $session || $session->course_id !== $this->course->id) {
            $this->errorMessage = __('Session not found.');

            return;
        }

        $sessionService->delete($sessionId);
        $this->successMessage = __('Session deleted successfully.');
    }

    public function render(SessionService $sessionService)
    {
        return view('livewire.courses.sessions-index', [
            'sessions' => $sessionService->forCourse($this->course->id, ['subtopics', 'materials', 'videoConferences']),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
