<?php

namespace App\Livewire\Submissions;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SubmissionShow extends Component
{
    public Submission $submission;

    public function mount(Submission $submission): void
    {
        $user = auth()->user();

        abort_unless(
            $submission->user_id === $user->id
                || $user->can('submissions.grade')
                || $user->can('submissions.view'),
            403
        );

        $this->submission = $submission->load(['assignment', 'user', 'reviewer']);
    }

    public function getPastAttemptsProperty(): Collection
    {
        if (! $this->submission->assignment->allow_multiple_submissions) {
            return new Collection;
        }

        return Submission::where('assignment_id', $this->submission->assignment_id)
            ->where('user_id', $this->submission->user_id)
            ->where('id', '!=', $this->submission->id)
            ->latest('submitted_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.submissions.submission-show', [
            'pastAttempts' => $this->pastAttempts,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Submission'])
            ->section('app-content');
    }
}
