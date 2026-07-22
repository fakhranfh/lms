<?php

namespace App\Livewire\Submissions;

use App\Models\Submission;
use Livewire\Component;

class SubmissionStatusChip extends Component
{
    public Submission $submission;

    public function mount(Submission $submission): void
    {
        $this->submission = $submission;
    }

    public function refreshStatus(): void
    {
        $this->submission->refresh();

        if ($this->submission->status->isTerminal()) {
            $this->dispatch('submission-graded', submissionId: $this->submission->id);
        }
    }

    public function render()
    {
        return view('livewire.submissions.submission-status-chip', [
            'isPolling' => ! $this->submission->status->isTerminal(),
        ]);
    }
}
