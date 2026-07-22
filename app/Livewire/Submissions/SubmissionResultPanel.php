<?php

namespace App\Livewire\Submissions;

use App\Models\Submission;
use Livewire\Component;

class SubmissionResultPanel extends Component
{
    public Submission $submission;

    public function mount(Submission $submission): void
    {
        $this->submission = $submission;
    }

    public function render()
    {
        return view('livewire.submissions.submission-result-panel');
    }
}
