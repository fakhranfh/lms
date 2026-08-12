<?php

namespace App\Livewire\Courses;

use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\QuizInstruction;
use App\Services\QuizInstructionService;
use App\Support\HtmlSanitizer;
use Livewire\Component;

class QuizInstructionEdit extends Component
{
    use WithRichTextEditor;

    public ?QuizInstruction $instruction = null;

    public string $content = '';

    public ?string $successMessage = null;

    public function mount(QuizInstructionService $quizInstructionService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        $this->instruction = $quizInstructionService->current();
        $this->content = $this->instruction ? $this->instruction->content : '';
    }

    public function save(QuizInstructionService $quizInstructionService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        $this->validate([
            'content' => 'required|string',
        ]);

        $data = [
            'content' => HtmlSanitizer::forum($this->promoteRichTextAttachments($this->content)),
            'updated_by' => auth()->id(),
        ];

        if ($this->instruction) {
            $this->instruction = $quizInstructionService->update($this->instruction->id, $data);
        } else {
            $this->instruction = $quizInstructionService->create($data);
        }

        $this->successMessage = __('Instructions saved.');
    }

    public function render()
    {
        return view('livewire.courses.quiz-instruction-edit', [
            'instruction' => $this->instruction,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Quiz Instructions'])
            ->section('app-content');
    }
}
