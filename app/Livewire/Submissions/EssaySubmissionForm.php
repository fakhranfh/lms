<?php

namespace App\Livewire\Submissions;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Services\SubmissionService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EssaySubmissionForm extends Component
{
    public Lesson $lesson;

    public Assignment $assignment;

    #[Validate('required|string')]
    public string $studentAnswer = '';

    public ?string $errorMessage = null;

    public function mount(Lesson $lesson, Assignment $assignment): void
    {
        abort_unless($assignment->lesson_id === $lesson->id, 404);
        abort_unless($assignment->is_published, 404);

        $user = auth()->user();
        abort_unless($assignment->lesson?->module?->course?->school_id === $user->school_id, 403);

        $this->lesson = $lesson;
        $this->assignment = $assignment;
    }

    public function submit(SubmissionService $submissionService)
    {
        $this->errorMessage = null;

        $this->validate();

        try {
            $submission = $submissionService->submit([
                'assignment_id' => $this->assignment->id,
                'user_id' => auth()->id(),
                'student_answer' => $this->studentAnswer,
            ]);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();

            return;
        }

        return redirect()->route('submissions.show', $submission);
    }

    public function render()
    {
        return view('livewire.submissions.essay-submission-form', [
            'pageTitle' => $this->assignment->title,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->assignment->title])
            ->section('app-content');
    }
}
