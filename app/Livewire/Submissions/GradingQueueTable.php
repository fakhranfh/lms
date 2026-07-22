<?php

namespace App\Livewire\Submissions;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Support\CurrentSchool;
use Livewire\Component;
use Livewire\WithPagination;

// Note: Batch actions (mark reviewed / resend failed) are out of scope — SubmissionService
// does not support them. Deferred per task instructions.
class GradingQueueTable extends Component
{
    use WithPagination;

    public ?string $status = null;

    public ?string $assignmentId = null;

    public ?string $submittedFrom = null;

    public ?string $submittedTo = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('submissions.grade'), 403);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAssignmentId(): void
    {
        $this->resetPage();
    }

    public function updatingSubmittedFrom(): void
    {
        $this->resetPage();
    }

    public function updatingSubmittedTo(): void
    {
        $this->resetPage();
    }

    public function render(CurrentSchool $currentSchool)
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $query = Submission::query()
            ->whereHas('assignment.lesson.module.course', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with(['assignment', 'user']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->assignmentId) {
            $query->where('assignment_id', $this->assignmentId);
        }

        if ($this->submittedFrom) {
            $query->whereDate('submitted_at', '>=', $this->submittedFrom);
        }

        if ($this->submittedTo) {
            $query->whereDate('submitted_at', '<=', $this->submittedTo);
        }

        $submissions = $query->latest('submitted_at')->paginate(50);

        $assignments = Assignment::whereHas('lesson.module.course', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->orderBy('title')->get();

        return view('livewire.submissions.grading-queue-table', [
            'submissions' => $submissions,
            'assignments' => $assignments,
            'statuses' => SubmissionStatus::cases(),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Grading Queue'])
            ->section('app-content');
    }
}
