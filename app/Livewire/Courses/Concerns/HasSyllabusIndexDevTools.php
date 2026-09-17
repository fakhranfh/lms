<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\SyllabusPolicyScope;
use App\Services\R2StorageService;
use Illuminate\Support\Str;

trait HasSyllabusIndexDevTools
{
    /**
     * Dev-only: fills the form with fake data so the UI can be exercised
     * without manually typing every field.
     */
    public function devAutofill(R2StorageService $r2StorageService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('syllabus.edit'), 403);

        $fileChip = $this->devFakeFileChip($r2StorageService);

        $this->courseDescription = $fileChip
            .'<p>This course introduces students to the core concepts, tools, and practices of the subject, '
            .'combining lectures, hands-on exercises, and real-world case studies to build both theoretical understanding and '
            .'practical skill.</p>';

        $policyContent = [
            'f2f_video' => 'Attendance is mandatory for all face-to-face and video conference sessions. Students arriving more than 15 minutes late will be marked absent.',
            'online' => 'Online session materials must be reviewed before the scheduled class. Cameras should remain on during discussions unless prior arrangements are made.',
            'general' => 'Academic honesty is expected at all times. Any form of plagiarism or cheating will result in disciplinary action per institutional policy.',
        ];

        $this->classPolicies = collect(SyllabusPolicyScope::cases())->values()->map(fn ($scope, $index) => [
            'scope' => $scope->value,
            'content' => $fileChip.'<p>'.$policyContent[$scope->value].'</p>',
            'order' => $index + 1,
        ])->all();

        $this->submissionAndCollection = $fileChip
            .'<p>All assignments must be submitted through the course portal before the stated deadline. '
            .'Late submissions will be penalized 10% per day unless an extension has been approved in advance.</p>';
        $this->tutorialActivityPlan = $fileChip
            .'<p>Each tutorial session begins with a short recap of the previous lecture, followed by guided '
            .'problem-solving in small groups and a class-wide discussion of solutions.</p>';

        $learningOutcomeContent = [
            'Explain the fundamental concepts and terminology covered in this course.',
            'Apply core techniques to solve practical, real-world problems.',
            'Evaluate different approaches and justify the choice of method for a given scenario.',
        ];

        $this->learningOutcomes = collect(range(1, 3))->map(fn ($index) => [
            'code' => 'LO'.$index,
            'description' => $fileChip.'<p>'.$learningOutcomeContent[$index - 1].'</p>',
            'order' => $index,
        ])->all();

        $this->evaluations = [[
            'class_type' => 'Quiz',
            'activities' => [
                ['activity' => 'Quiz 1', 'weight' => '50', 'order' => 1, 'learning_outcome_indices' => [0]],
                ['activity' => 'Quiz 2', 'weight' => '50', 'order' => 2, 'learning_outcome_indices' => [1, 2]],
            ],
        ]];

        $this->rubricProficiencyLevels = [
            ['label' => 'Excellent', 'score_min' => '80', 'score_max' => '100', 'order' => 1],
            ['label' => 'Good', 'score_min' => '60', 'score_max' => '79', 'order' => 2],
            ['label' => 'Needs Improvement', 'score_min' => '0', 'score_max' => '59', 'order' => 3],
        ];

        $keyIndicatorContent = [
            'Correctly defines and explains key terminology.',
            'Applies the appropriate technique to solve the given problem.',
            'Justifies the chosen approach with sound reasoning.',
        ];

        $this->rubricKeyIndicators = collect(range(1, 3))->map(fn ($index) => [
            'learning_outcome_index' => (string) ($index - 1),
            'code' => '1.'.$index,
            'description' => $keyIndicatorContent[$index - 1],
            'order' => $index,
        ])->all();

        $rubricCellContent = [
            'Consistently meets this indicator with clear, well-organized work.',
            'Mostly meets this indicator with minor gaps.',
            'Rarely meets this indicator; significant gaps remain.',
        ];

        $this->rubricCells = collect(range(0, 2))->mapWithKeys(fn ($kiIndex) => [
            $kiIndex => collect(range(0, 2))->mapWithKeys(fn ($plIndex) => [
                $plIndex => $fileChip.'<p>'.$rubricCellContent[$plIndex].'</p>',
            ])->all(),
        ])->all();

        $this->teachingLearningStrategies = $fileChip
            .'<p>This course uses a blended approach combining interactive lectures, collaborative '
            .'group work, and self-paced online modules to accommodate different learning styles.</p>';
        $this->textbooks = $fileChip
            .'<p>Primary textbook to be announced by the instructor at the start of the term. Supplementary readings '
            .'will be provided through the course portal.</p>';
        $this->competencyMap = $fileChip
            .'<p>This course contributes to the program\'s core competencies in analytical thinking, technical '
            .'proficiency, and effective communication.</p>';
        $this->videoOverview = $fileChip
            .'<p>A short video introducing the course goals, structure, and instructor will be shared before the '
            .'first session.</p>';

        // Rich-text editors run wire:ignore, so their DOM is silent to property
        // changes; they only refresh when told to via this browser event.
        $this->dispatch('rich-text-set-content', id: 'course-description', value: $this->courseDescription);
        foreach ($this->classPolicies as $index => $policy) {
            $this->dispatch('rich-text-set-content', id: "class-policy-{$index}", value: $policy['content']);
        }
        $this->dispatch('rich-text-set-content', id: 'submission-and-collection', value: $this->submissionAndCollection);
        $this->dispatch('rich-text-set-content', id: 'tutorial-activity-plan', value: $this->tutorialActivityPlan);
        foreach ($this->learningOutcomes as $index => $lo) {
            $this->dispatch('rich-text-set-content', id: "learning-outcome-{$index}", value: $lo['description']);
        }
        foreach ($this->rubricCells as $kiIndex => $row) {
            foreach ($row as $plIndex => $description) {
                $this->dispatch('rich-text-set-content', id: "rubric-cell-{$kiIndex}-{$plIndex}", value: $description);
            }
        }
        $this->dispatch('rich-text-set-content', id: 'teaching-learning-strategies', value: $this->teachingLearningStrategies);
        $this->dispatch('rich-text-set-content', id: 'textbooks', value: $this->textbooks);
        $this->dispatch('rich-text-set-content', id: 'competency-map', value: $this->competencyMap);
        $this->dispatch('rich-text-set-content', id: 'video-overview', value: $this->videoOverview);
    }

    /**
     * Uploads a dummy PDF and renders it as the same file-chip markup the
     * editor's own attach-file button produces, so autofill exercises the
     * attachment flow instead of leaving the editor's attachments untested.
     */
    private function devFakeFileChip(R2StorageService $r2StorageService): string
    {
        $content = "%PDF-1.4\n"
            .'1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj'."\n"
            .'2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj'."\n"
            .'3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Resources<<>>/Contents 4 0 R>>endobj'."\n"
            .'4 0 obj<</Length 44>>stream'."\n"
            .'BT /F1 18 Tf 20 100 Td (Syllabus Attachment) Tj ET'
            ."\nendstream endobj\n"
            .'trailer<</Size 5/Root 1 0 R>>'."\n"
            .'%%EOF';

        // Uploaded straight to its final location (not staged under temp/) since
        // this single chip gets reused across every rich-text field below —
        // promoteRichTextAttachments() would otherwise try to promote the same
        // already-moved temp file more than once and fail with a stale key.
        $key = $r2StorageService->schoolPrefix().$this->richTextAttachmentFolder().'/dev-generated/syllabus-'.Str::uuid().'.pdf';
        $url = $r2StorageService->uploadRawContent($key, $content, 'application/pdf');

        return '<p>'
            .'<a href="'.$url.'" target="_blank" rel="noopener" contenteditable="false" class="rte-file-chip">'
            .'<span class="rte-file-chip-icon rte-file-chip-icon--pdf">PDF</span>'
            .'<span class="rte-file-chip-info">'
            .'<span class="rte-file-chip-name">syllabus-attachment.pdf</span>'
            .'<span class="rte-file-chip-size">1.2 KB</span>'
            .'</span>'
            .'</a>'
            .'</p>';
    }
}
