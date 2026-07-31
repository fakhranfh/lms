<?php

namespace App\Livewire\Submissions;

use App\Models\Submission;
use App\Services\SubmissionService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class OverrideScoreModal extends Component
{
    public Submission $submission;

    #[Validate('required|numeric|min:0|max:100')]
    public ?float $teacherScore = null;

    #[Validate('required|string')]
    public string $teacherFeedback = '';

    public function mount(Submission $submission): void
    {
        abort_unless(auth()->user()->can('submissions.override-grade'), 403);

        $this->submission = $submission->load(['assignment', 'user']);
        $this->teacherScore = $submission->teacher_score !== null ? (float) $submission->teacher_score : ($submission->ai_score !== null ? (float) $submission->ai_score : null);
        $this->teacherFeedback = $submission->teacher_feedback ?? '';
    }

    public function save(SubmissionService $submissionService)
    {
        $this->validate();

        $submissionService->overrideScore(
            $this->submission->id,
            $this->teacherScore,
            $this->teacherFeedback,
            auth()->user()
        );

        return redirect()->route('submissions.show', $this->submission)->with('success', 'Score overridden successfully.');
    }

    public function render()
    {
        return view('livewire.submissions.override-score-modal')
            ->extends('layouts.app', ['topbarTitle' => 'Override Score'])
            ->section('app-content');
    }
}
