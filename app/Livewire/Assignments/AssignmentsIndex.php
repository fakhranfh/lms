<?php

namespace App\Livewire\Assignments;

use App\Models\Assignment;
use App\Services\AssignmentService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class AssignmentsIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('assignments.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('delete-confirmed')]
    public function destroy(string $id, AssignmentService $assignmentService): void
    {
        abort_unless(auth()->user()->can('assignments.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $assignmentService->find($id)) {
            $this->errorMessage = __('Assignment not found.');

            return;
        }

        $assignmentService->delete($id);
        $this->successMessage = __('Assignment deleted successfully.');
        $this->resetPage();
    }

    public function render(CurrentSchool $currentSchool)
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $query = Assignment::query()
            ->whereHas('lesson.module.course', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with(['lesson.module.course']);

        if ($this->search) {
            $query->whereLike('title', "%{$this->search}%", caseSensitive: false);
        }

        $assignments = $query->withCount('submissions')->latest()->paginate(10);

        return view('livewire.assignments.assignments-index', [
            'assignments' => $assignments,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Assignments'])
            ->section('app-content');
    }
}
