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
    public ?float $instructorScore = null;

    #[Validate('required|string')]
    public string $instructorFeedback = '';

    public function mount(Submission $submission): void
    {
        abort_unless(auth()->user()->can('submissions.override-grade'), 403);

        $this->submission = $submission->load(['assignment', 'user']);
        $this->instructorScore = $submission->instructor_score !== null ? (float) $submission->instructor_score : ($submission->ai_score !== null ? (float) $submission->ai_score : null);
        $this->instructorFeedback = $submission->instructor_feedback ?? '';
    }

    public function save(SubmissionService $submissionService)
    {
        $this->validate();

        $submissionService->overrideScore(
            $this->submission->id,
            $this->instructorScore,
            $this->instructorFeedback,
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
