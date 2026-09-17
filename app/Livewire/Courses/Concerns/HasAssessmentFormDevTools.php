<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\MediaLibraryService;
use App\Services\R2StorageService;
use App\Support\AssessmentTypeLabel;

trait HasAssessmentFormDevTools
{
    /**
     * Dev-only: fills the form with fake data so the UI can be exercised
     * without manually typing every field.
     */
    public function devAutofill(MediaLibraryService $mediaLibraryService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create') || auth()->user()->can('assessment.edit'), 403);

        $this->title = AssessmentTypeLabel::forType($this->assessmentType).' - Week '.random_int(1, 14).' Practice';
        $this->weight = (string) $this->assessmentType->defaultWeight();
        $this->startDate = now()->format('Y-m-d\TH:i');
        $this->endDate = now()->addWeek()->format('Y-m-d\TH:i');
        $this->status = 'draft';

        if (! $this->usesQuestions()) {
            return;
        }

        $materialIds = $this->devMaterialIds($this->course->school_id, $mediaLibraryService, $r2StorageService);

        $questionContent = [
            'Explain the main concept covered in this week\'s lecture and give one real-world example of it in use.',
            'Compare and contrast two approaches discussed in class, and justify which one you would choose for a given scenario.',
            'Given the sample dataset provided in the course materials, describe the steps you would take to solve the problem.',
        ];

        $pointsDistribution = [30, 30, 40];

        $this->questions = collect($questionContent)->values()->map(fn ($description, $index) => [
            'id' => null,
            'description' => '<p>'.$description.'</p>',
            'points' => (string) $pointsDistribution[$index],
            'selectedMaterialIds' => $materialIds,
            'materialSearch' => '',
        ])->all();

        // The question descriptions run wire:ignore, so their DOM is silent
        // to property changes; they only refresh when told to via this
        // browser event.
        foreach ($this->questions as $index => $question) {
            $this->dispatch('rich-text-set-content', id: "question-{$index}", value: $question['description']);
        }
    }
}
