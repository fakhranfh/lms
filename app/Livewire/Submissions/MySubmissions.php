<?php

namespace App\Livewire\Submissions;

use App\Models\Submission;
use Livewire\Component;
use Livewire\WithPagination;

class MySubmissions extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('submissions.view'), 403);
    }

    public function render()
    {
        $submissions = Submission::query()
            ->where('user_id', auth()->id())
            ->with('assignment')
            ->latest('submitted_at')
            ->paginate(20);

        return view('livewire.submissions.my-submissions', [
            'submissions' => $submissions,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'My Submissions'])
            ->section('app-content');
    }
}
